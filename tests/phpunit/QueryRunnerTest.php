<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Tests;

use Keboola\SynapseTransformation\Configuration\Block;
use Keboola\SynapseTransformation\Exception\UserException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\Test\TestLogger;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL;
use Keboola\SynapseTransformation\QueryFormatter;
use Keboola\SynapseTransformation\QueryRunner;

class QueryRunnerTest extends TestCase
{
    private TestLogger $logger;

    private QueryRunner $queryRunner;

    /** @var callable */
    private $onExec;

    /** @var callable */
    private $onQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $queryFormatter = new QueryFormatter();
        $this->logger = new TestLogger();
        $driverConnection = $this
            ->getMockBuilder(DBAL\Driver\SQLSrv\SQLSrvConnection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['exec', 'query'])
            ->getMock();

        // Mock connection->exec(...) calls
        $this->onExec = function (): void {
        };
        $driverConnection
            ->expects($this->any())
            ->method('exec')
            ->willReturnCallback(function (string $query) use ($queryFormatter) {
                $this->logger->debug('MOCK EXEC: ' . $queryFormatter->formatToLog($query));
                return ($this->onExec)($query);
            });

        // Mock connection->query(...) calls
        $this->onQuery = function () {
            return $this->createStatementMock();
        };
        $driverConnection
            ->expects($this->any())
            ->method('query')
            ->will($this->returnCallback(function (string $query) use ($queryFormatter) {
                $this->logger->debug('MOCK QUERY: ' . $queryFormatter->formatToLog($query));
                return ($this->onQuery)($query);
            }));

        $driverMock = $this->createMock(DBAL\Driver::class);
        $driverMock->expects($this->any())->method('connect')->willReturn($driverConnection);
        $platform = $this->getMockForAbstractClass(DBAL\Platforms\AbstractPlatform::class);
        $connection = new DBAL\Connection(['platform' => $platform], $driverMock);
        $this->queryRunner = new QueryRunner($this->logger, $connection, new QueryFormatter());
    }

    public function testIgnoreEmpty(): void
    {
        $blocks = $this->createBlocks(['', 'INSERT INTO `temp` VALUES (1,2,3);']);
        $this->queryRunner->processBlocks($blocks);
        Assert::assertSame([
            'Processing block "Block Name".',
            'Processing code "Code 1".',
            'Ignoring empty query.',
            'Running query "INSERT INTO `temp` VALUES (1, 2, 3);".',
            'MOCK EXEC: INSERT INTO `temp` VALUES (1, 2, 3);',
        ], $this->getLoggedMessages());
    }

    public function testIgnoreSelect(): void
    {
        $blocks = $this->createBlocks(['  SeLeCt foo FROM bar  ', 'INSERT INTO `temp` VALUES (1,2,3);']);
        $this->queryRunner->processBlocks($blocks);
        Assert::assertSame([
            'Processing block "Block Name".',
            'Processing code "Code 1".',
            'Ignoring select query "SeLeCt foo FROM bar".',
            'Running query "INSERT INTO `temp` VALUES (1, 2, 3);".',
            'MOCK EXEC: INSERT INTO `temp` VALUES (1, 2, 3);',
        ], $this->getLoggedMessages());
    }

    public function testQueryFailed(): void
    {
        $this->onExec = function (): void {
            throw new \Exception('Some error.');
        };

        $blocks = $this->createBlocks(['INSERT INTO `temp` VALUES (1,2,3);']);
        $this->expectException(UserException::class);
        $this->expectExceptionMessage(
            'Query "INSERT INTO `temp` VALUES (1, 2, 3);" ' .
            'from block "Block Name" and code "Code 1" failed: "Some error."'
        );
        $this->queryRunner->processBlocks($blocks);
    }

    private function getLoggedMessages(): array
    {
        return array_map(fn(array $log) => $log['message'], $this->logger->records);
    }

    private function createStatementMock(array $returnValue = []): MockObject
    {
        $statementMock = $this->createMock(DBAL\Statement::class);
        $statementMock->expects($this->any())
            ->method('fetchAll')
            ->with(DBAL\FetchMode::COLUMN)
            ->will($this->returnValue($returnValue));
        return $statementMock;
    }

    private function createBlocks(array $script): array
    {
        return [new Block([
            'name' => 'Block Name',
            'codes' => [
                [
                    'name' => 'Code 1',
                    'script' => $script,
                ],
            ],
        ])];
    }
}
