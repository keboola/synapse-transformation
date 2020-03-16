<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Configuration;

class OutputTableMapping
{
    private string $source;

    private string $destination;

    public function __construct(array $data)
    {
        $this->source = $data['source'];
        $this->destination = $data['destination'];
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }
}
