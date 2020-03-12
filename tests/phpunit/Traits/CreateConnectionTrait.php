<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Tests\Traits;

use Doctrine\DBAL\Connection;
use Keboola\SynapseTransformation\Tests\Tools\TestConnectionFactory;

trait CreateConnectionTrait
{
    public function createConnection(int $queryTimeout = 30): Connection
    {
        return TestConnectionFactory::createConnection($queryTimeout);
    }
}
