<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Configuration;

class Script
{
    private string $rawSql;

    public function __construct(string $rawSql)
    {
        $this->rawSql = $rawSql;
    }

    public function getRawSql(): string
    {
        return $this->rawSql;
    }
}
