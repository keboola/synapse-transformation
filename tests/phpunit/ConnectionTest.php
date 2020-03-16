<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Tests;

use Doctrine\DBAL\DBALException;
use Keboola\SynapseTransformation\Tests\Traits\CreateConnectionTrait;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    use CreateConnectionTrait;

    public function testQueryTimeoutExpired(): void
    {
        $this->expectException(DBALException::class);
        $this->expectExceptionMessage('Query timeout expired');
        $timeout = 1;
        $sqlDelay = 3;
        $this->runQueryWithTimeout($timeout, $sqlDelay);
    }

    public function testQueryTimeoutNotExpired(): void
    {
        $timeout = 10;
        $sqlDelay = 3;
        $this->runQueryWithTimeout($timeout, $sqlDelay);
        $this->addToAssertionCount(1); // Assert no error
    }

    public function testQuery(): void
    {
        Assert::assertSame(
            [['A' => '1', 'B' => '2']],
            $this->createConnection()->query('SELECT 1 AS A, 2 AS B')->fetchAll()
        );
    }

    public function testExec(): void
    {
        $connection = $this->createConnection();

        // Exec returns number of affected columns
        Assert::assertSame(0, $connection->exec(
            'CREATE TABLE #temp (product_name VARCHAR(100)) WITH (LOCATION = USER_DB)'
        ));
        Assert::assertSame(1, $connection->exec(
            "INSERT INTO #temp (product_name) VALUES ('test')"
        ));
    }

    private function runQueryWithTimeout(int $timeout, int $sqlDelay): void
    {
        $this
            ->createConnection($timeout)
            ->exec(sprintf(
            // Synapse DB doesn't support WAITFOR DELAY
            // https://feedback.azure.com/forums/307516-azure-synapse-analytics/suggestions/31120816-add-waitfor-command-to-sql-datawarehouse
                'DECLARE @d datetime = GETDATE(); DECLARE @x int; ' .
                "WHILE (DATEDIFF(SECOND, @d, GETDATE()) < %d) BEGIN SET @x = 1; END; SELECT '%d';",
                $sqlDelay,
                rand(0, 1000000) // prevent query cache
            ));
    }
}
