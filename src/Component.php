<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation;

use Psr\Log\LoggerInterface;
use Keboola\Component\BaseComponent;
use Keboola\SynapseTransformation\Configuration\Config;
use Keboola\SynapseTransformation\Configuration\ConfigDefinition;
use Keboola\SynapseTransformation\Platform\ConnectionFactory;

class Component extends BaseComponent
{
    private QueryRunner $queryRunner;

    private ManifestWriter $manifestWriter;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        $connectionFactory = new ConnectionFactory();
        $connection = $connectionFactory->createFromConfig($this->getConfig());
        $queryFormatter = new QueryFormatter();
        $this->queryRunner = new QueryRunner($logger, $connection, $queryFormatter);
        $this->manifestWriter = new ManifestWriter($logger, $connection, $this->getManifestManager());
    }

    protected function run(): void
    {
        $config = $this->getConfig();
        $this->queryRunner->processBlocks($config->getBlocks());
        $this->manifestWriter->processTables($config->getOutputTablesMapping());
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
