<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Tests\Tools;

use Doctrine\DBAL\Connection;
use Keboola\SynapseTransformation\Platform\ConnectionFactory;

trait TestConnectionFactory
{
    public static function createConnection(int $queryTimeout = 30): Connection
    {
        $factory = new ConnectionFactory();
        return $factory->create(
            (string) getenv('SYNAPSE_HOST'),
            (int) getenv('SYNAPSE_PORT'),
            (string) getenv('SYNAPSE_DATABASE'),
            (string) getenv('SYNAPSE_USER'),
            (string) getenv('SYNAPSE_PASSWORD'),
            $queryTimeout,
        );
    }
}
