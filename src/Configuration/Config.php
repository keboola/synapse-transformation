<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Configuration;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getHost(): string
    {
        return $this->getValue(['authorization', 'workspace', 'host']);
    }

    public function getPort(): int
    {
        return $this->getValue(['authorization', 'workspace', 'port']);
    }

    public function getUser(): string
    {
        return $this->getValue(['authorization', 'workspace', 'user']);
    }

    public function getPassword(): string
    {
        return $this->getValue(['authorization', 'workspace', 'password']);
    }

    public function getDatabase(): string
    {
        return $this->getValue(['authorization', 'workspace', 'database']);
    }

    public function getQueryTimeout(): int
    {
        return (int) $this->getValue(['parameters', 'query_timeout']);
    }

    /**
     * @return array|Block[]
     */
    public function getBlocks(): array
    {
        return array_map(
            fn(array $data) => new Block($data),
            $this->getValue(['parameters', 'blocks'])
        );
    }

    /**
     * @return array|OutputTableMapping[]
     */
    public function getOutputTablesMapping(): array
    {
        return array_map(
            fn(array $data) => new OutputTableMapping($data),
            $this->getValue(['storage', 'output', 'tables'], [])
        );
    }
}
