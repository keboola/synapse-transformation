<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\FunctionalTests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Keboola\Csv\CsvWriter;
use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecificationInterface;
use Keboola\DatadirTests\DatadirTestsProviderInterface;
use Keboola\SynapseTransformation\Tests\Traits\CreateConnectionTrait;
use Symfony\Component\Filesystem\Filesystem;

class DatadirTest extends AbstractDatadirTestCase
{
    use CreateConnectionTrait;

    private const DB_DUMP_IGNORED_METADATA = [
        'TABLE_QUALIFIER',
        'TABLE_OWNER',
    ];

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->dropAllTables();
    }

    /**
     * @dataProvider provideDatadirSpecifications
     */
    public function testDatadir(DatadirTestSpecificationInterface $specification): void
    {
        $tempDatadir = $this->getTempDatadir($specification);

        // Replace environment variables in config.json
        $configPath = $tempDatadir->getTmpFolder() . '/config.json';
        if (file_exists($configPath)) {
            $config = (string) file_get_contents($configPath);
            $config = preg_replace_callback('~\$([a-zA-Z0-9_\-]+)~', fn($m) => getenv($m[1]), $config);
            file_put_contents($configPath, $config);
        }

        // Setup initial db state
        $this->dropAllTables();

        // Run script
        $process = $this->runScript($tempDatadir->getTmpFolder());

        // Dump database data & create statement after running the script
        $this->dumpAllTables($tempDatadir->getTmpFolder());

        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    /**
     * @return DatadirTestsProviderInterface[]
     */
    protected function getDataProviders(): array
    {
        return [
            new DatadirTestsProvider($this->getTestFileDir()),
        ];
    }

    protected function dropAllTables(): void
    {
        // Drop all tables
        $connection = $this->createConnection();
        foreach ($connection->getSchemaManager()->listTables() as $table) {
            $connection->query(sprintf('DROP TABLE %s', $connection->quoteIdentifier($table->getName())));
        }
    }


    protected function dumpAllTables(string $tmpDir): void
    {
        // Create output dir
        $dumpDir = $tmpDir . '/out/db-dump';
        $fs = new Filesystem();
        $fs->mkdir($dumpDir, 0777);

        // Create connection and get tables
        $connection = $this->createConnection();
        foreach ($connection->getSchemaManager()->listTables() as $table) {
            $this->dumpTable($connection, $table->getName(), $dumpDir);
        }
    }

    protected function dumpTable(Connection $connection, string $table, string $dumpDir): void
    {
        // Generate create statement
        $metadata = $connection
            ->query(sprintf('exec sp_columns @table_name = N%s', $connection->quote($table)))
            ->fetchAll();

        // Ignore non-static keys
        $metadata = array_map(fn(array $item) => array_filter(
            $item,
            fn(string $key) => !in_array($key, self::DB_DUMP_IGNORED_METADATA, true),
            ARRAY_FILTER_USE_KEY
        ), $metadata);

        // Save create statement
        file_put_contents(
            sprintf('%s/%s.metadata.json', $dumpDir, $table),
            json_encode($metadata, JSON_PRETTY_PRINT)
        );

        // Dump data
        $this->dumpTableData($connection, $table, $dumpDir);
    }

    protected function dumpTableData(
        Connection $connection,
        string $table,
        string $dumpDir
    ): void {
        $csv = new CsvWriter(sprintf('%s/%s.data.csv', $dumpDir, $table));

        // Write header
        $columns = array_values(array_map(
            fn(Column $col) => $col->getName(),
            $connection->getSchemaManager()->listTableColumns($table)
        ));
        $csv->writeRow($columns);

        // Write data
        $data = $connection->query(sprintf(
            'SELECT * FROM %s ORDER BY %s',
            $connection->quoteIdentifier($table),
            $connection->quoteIdentifier($columns[0])
        ))->fetchAll();
        foreach ($data as $row) {
            $csv->writeRow($row);
        }
    }
}
