<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Platform;

use PDO;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use Keboola\SynapseTransformation\Configuration\Config;

class ConnectionFactory
{
    public function createFromConfig(Config $config): Connection
    {
        return $this->create(
            $config->getHost(),
            $config->getPort(),
            $config->getDatabase(),
            $config->getUser(),
            $config->getPassword(),
            $config->getQueryTimeout()
        );
    }

    public function create(
        string $host,
        int $port,
        string $database,
        string $user,
        string $password,
        int $queryTimeout
    ): Connection {
        // https://docs.microsoft.com/en-us/azure/sql-data-warehouse/sql-data-warehouse-connection-strings
        return DriverManager::getConnection([
            'driver' => 'pdo_sqlsrv',
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'dbname' => $database,
            'driverOptions' => [
                'LoginTimeout' => 30,
                'ConnectRetryCount' => 3,
                'ConnectRetryInterval' => 10,
                PDO::SQLSRV_ATTR_QUERY_TIMEOUT => $queryTimeout,
            ],
        ]);
    }
}
