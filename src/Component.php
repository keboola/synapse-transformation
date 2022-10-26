<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Keboola\Component\BaseComponent;
use Keboola\SynapseTransformation\Configuration\Config;
use Keboola\SynapseTransformation\Configuration\ConfigDefinition;
use Keboola\SynapseTransformation\Platform\ConnectionFactory;

class Component extends BaseComponent
{
    private QueryRunner $queryRunner;

    private ManifestWriter $manifestWriter;

    private Connection $connection;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        $connectionFactory = new ConnectionFactory();
        $this->connection = $connectionFactory->createFromConfig($this->getConfig());
        $queryFormatter = new QueryFormatter();
        $this->queryRunner = new QueryRunner($logger, $this->connection, $queryFormatter);
        $this->manifestWriter = new ManifestWriter($logger, $this->connection, $this->getManifestManager());
    }

    protected function run(): void
    {
        $config = $this->getConfig();
        $this->setWlmContext($this->connection, $this->getConfig()->getWlmContext());
        $this->queryRunner->processBlocks($config->getBlocks());
        $this->manifestWriter->processTables($config->getOutputTablesMapping());
        $this->setWlmContext($this->connection);
    }

    private function setWlmContext(Connection $connection, ?string $wlmContext = null): void
    {
        $sqlTemplate = <<<SQL
EXEC sys.sp_set_session_context @key = 'wlm_context', @value = %s;
SQL;

        $sql = sprintf(
            $sqlTemplate,
            $wlmContext ? $connection->quote($wlmContext) : 'null'
        );

        $connection->query($sql)->execute();
    }

    public function getConfig(): Config
    {
        $config = parent::getConfig();
        assert($config instanceof Config);
        return $config;
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }
}
