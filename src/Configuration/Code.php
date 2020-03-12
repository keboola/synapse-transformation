<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Configuration;

class Code
{
    private string $name;

    private array $scripts;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->scripts = array_map(fn(string $sql) => new Script($sql), $data['script']);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array|Script[]
     */
    public function getScripts(): array
    {
        return $this->scripts;
    }
}
