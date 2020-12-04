<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Configuration;

use InvalidArgumentException;
use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public const DEFAULT_QUERY_TIMEOUT = 7200;

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
        // Get value from the config
        $value = $this->getValueOrNull(['parameters', 'query_timeout']);
        if ($value) {
            return $value;
        }

        // Get value from the image parameters
        $value = $this->getValueOrNull(['image_parameters', 'default_query_timeout']);
        if ($value) {
            return $value;
        }

        // Default value
        return self::DEFAULT_QUERY_TIMEOUT;
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

    /**
     * @return mixed
     */
    private function getValueOrNull(array $keys)
    {
        try {
            return $this->getValue($keys);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }
}
