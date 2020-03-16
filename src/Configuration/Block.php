<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Configuration;

class Block
{
    private string $name;

    private array $codes;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->codes = array_map(fn(array $data) => new Code($data), $data['codes']);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array|Code[]
     */
    public function getCodes(): array
    {
        return $this->codes;
    }
}
