<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation;

use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use Keboola\Component\Manifest\ManifestManager;
use Keboola\Component\Manifest\ManifestManager\Options\OutTableManifestOptions;
use Keboola\Datatype\Definition\Synapse;
use Keboola\Datatype\Definition\Synapse as SynapseColumnType;
use Keboola\SynapseTransformation\Configuration\OutputTableMapping;
use Keboola\SynapseTransformation\Exception\UserException;
use Keboola\TableBackendUtils\Column\ColumnInterface;
use Keboola\TableBackendUtils\ReflectionException;
use Keboola\TableBackendUtils\Table\SynapseTableReflection;

class ManifestWriter
{
    private LoggerInterface $logger;

    private Connection $connection;

    private ManifestManager $manifestManager;

    public function __construct(LoggerInterface $logger, Connection $connection, ManifestManager $manifestManager)
    {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->manifestManager = $manifestManager;
    }

    /**
     * @param array|OutputTableMapping[] $tables
     */
    public function processTables(array $tables): void
    {
        $schemaName = $this->connection->query('SELECT SCHEMA_NAME()')->fetchColumn();

        $missingTables = [];
        foreach ($tables as $table) {
            if (!$this->processTable($schemaName, $table)) {
                $missingTables[] = $table->getSource();
            }
        }

        // Are there any missing tables?
        if ($missingTables) {
            throw new UserException(sprintf(
                '%s "%s" specified in output were not created by the transformation.',
                count($missingTables) === 1 ? 'Table' : 'Tables',
                implode('", "', $missingTables)
            ));
        }
    }

    private function processTable(string $schemaName, OutputTableMapping $table): bool
    {
        $tableReflection = new SynapseTableReflection($this->connection, $schemaName, $table->getSource());
        try {
            $columns = $tableReflection->getColumnsDefinitions();
        } catch (ReflectionException $e) {
            // Table is missing
            return false;
        }

        $metadata = [];
        /** @var ColumnInterface  $column */
        foreach ($columns as $column) {
            $name = $column->getColumnName();
            $type = $column->getColumnDefinition();
            assert($type instanceof SynapseColumnType);
            $metadata[$name] = $type->toMetadata();
        }

        $data = new OutTableManifestOptions();
        $data->setColumns(array_keys($metadata));
        $data->setColumnMetadata($metadata);
        $this->manifestManager->writeTableManifest($table->getSource(), $data);
        return true;
    }
}
