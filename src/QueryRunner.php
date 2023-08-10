<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Keboola\SynapseTransformation\Configuration\Code;
use Keboola\SynapseTransformation\Configuration\Script;
use Keboola\SynapseTransformation\Configuration\Block;
use Keboola\SynapseTransformation\Exception\UserException;
use Psr\Log\LoggerInterface;
use Retry\BackOff\ExponentialBackOffPolicy;
use Retry\Policy\SimpleRetryPolicy;
use Retry\RetryProxy;

class QueryRunner
{
    private LoggerInterface $logger;

    private Connection $connection;

    private QueryFormatter $queryFormatter;

    public function __construct(LoggerInterface $logger, Connection $connection, QueryFormatter $queryFormatter)
    {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->queryFormatter = $queryFormatter;
    }

    /**
     * @param array|Block[] $blocks
     */
    public function processBlocks(array $blocks): void
    {
        foreach ($blocks as $block) {
            $this->processBlock($block);
        }
    }

    private function processBlock(Block $block): void
    {
        $this->logger->info(sprintf('Processing block "%s".', $block->getName()));
        foreach ($block->getCodes() as $code) {
            $this->processCode($block, $code);
        }
    }

    private function processCode(Block $block, Code $code): void
    {
        $this->logger->info(sprintf('Processing code "%s".', $code->getName()));
        foreach ($code->getScripts() as $script) {
            $this->processScript($block, $code, $script);
        }
    }

    private function processScript(Block $block, Code $code, Script $script): void
    {
        // Remove comments
        $sql = $this->queryFormatter->removeComments($script->getRawSql());
        $sqlToLog = $this->queryFormatter->formatToLog($sql);

        // Skip empty query
        if (!$sql) {
            return;
        }

        // Skip select
        if (strtoupper(substr($this->queryFormatter->removeWhiteSpace($sql), 0, 6)) === 'SELECT' &&
            !strpos(strtoupper($this->queryFormatter->removeWhiteSpace($sql)), 'INTO')) {
            $this->logger->info(sprintf('Ignoring select query "%s".', $sqlToLog));
            return;
        }

        // Run
        $this->logger->info(sprintf('Running query "%s".', $sqlToLog));
        $retryProxy = new RetryProxy(
            new SimpleRetryPolicy(5),
            new ExponentialBackOffPolicy(),
            $this->logger
        );

        try {
            $retryProxy->call(function () use ($sql): void {
                $this->connection->exec($sql);
            });
        } catch (\Throwable $originException) {
            $exception = $originException;

            // Unwrap to get better error message
            if ($exception instanceof DBALException) {
                $exception =  $exception->getPrevious() ?? $exception;
            }

            // Remove driver prefix to get simpler message
            $message = preg_replace('~^SQLSTATE\[.+\[SQL Server\]~i', '', $exception->getMessage());

            throw new UserException(sprintf(
                'Query "%s" from block "%s" and code "%s" failed: "%s"',
                $sqlToLog,
                $block->getName(),
                $code->getName(),
                $message,
            ), 0, $originException);
        }
    }
}
