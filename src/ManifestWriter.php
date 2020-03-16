<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation;

use Doctrine\DBAL\Connection;
use Keboola\Component\Manifest\ManifestManager;
use Keboola\Component\Manifest\ManifestManager\Options\OutTableManifestOptions;
use Keboola\Datatype\Definition\Exception\InvalidLengthException;
use Keboola\SynapseTransformation\Configuration\OutputTableMapping;
use Keboola\SynapseTransformation\Exception\UserException;
use Keboola\Datatype\Definition\Synapse as SynapseColumnType;
use Psr\Log\LoggerInterface;

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
        $missingTables = [];
        foreach ($tables as $table) {
            if (!$this->processTable($table)) {
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

    private function processTable(OutputTableMapping $table): bool
    {
        $columns = $this->connection
            ->query(sprintf(
                'SELECT c.name, t.name AS type, c.max_length AS length, ' .
                'c.is_nullable AS nullable, d.definition AS [default] ' .
                'FROM sys.columns AS c ' .
                'JOIN sys.types   AS t  ON c.user_type_id = t.user_type_id ' .
                'JOIN sys.objects AS o  ON c.object_id = o.object_id '.
                'JOIN sys.schemas AS s  ON o.schema_id = s.schema_id ' .
                'LEFT JOIN sys.default_constraints d  ON c.default_object_id = d.object_id ' .
                'WHERE o.type = \'U\' AND s.name = SCHEMA_NAME() AND o.name = %s',
                $this->connection->quote($table->getSource()),
            ))
            ->fetchAll();
        if (empty($columns)) {
            $missingTables[] = $table->getSource();
            return false;
        }

        $columnNames = [];
        $metadata = [];
        foreach ($columns as $column) {
            $columnNames[] = $column['name'];
            $typeOptions = [
                'length' => $column['length'],
                'nullable' => $column['nullable'],
                'default' => $column['default'],
            ];

            try {
                $type = new SynapseColumnType($column['type'], $typeOptions);
            } catch (InvalidLengthException $exception) {
                // The database also reports the length for types that have it fixed
                unset($typeOptions['length']);
                $type = new SynapseColumnType($column['type'], $typeOptions);
            }

            $metadata[$column['name']] = $type->toMetadata();
        }

        $data = new OutTableManifestOptions();
        $data->setColumns($columnNames);
        $data->setColumnMetadata($metadata);
        $this->manifestManager->writeTableManifest($table->getSource(), $data);
        return true;
    }
}
