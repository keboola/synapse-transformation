<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Tests;

use Keboola\SynapseTransformation\Configuration\Config;
use Keboola\SynapseTransformation\Configuration\ConfigDefinition;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigTest extends TestCase
{
    public function testConfigObjectsMapping(): void
    {
        $config = new Config($this->getComplexValidConfig(), new ConfigDefinition());

        // Authorization
        Assert::assertSame('synapse.db', $config->getHost());
        Assert::assertSame(1234, $config->getPort());
        Assert::assertSame('test-user', $config->getUser());
        Assert::assertSame('test-password', $config->getPassword());
        Assert::assertSame('my-database', $config->getDatabase());

        // Query timeout
        Assert::assertSame(1000, $config->getQueryTimeout());

        // Blocks
        $blocks = $config->getBlocks();

        // Block 1
        Assert::assertCount(3, $blocks);
        Assert::assertSame('Block 1', $blocks[0]->getName());
        Assert::assertCount(0, $blocks[0]->getCodes());

        // Block 2
        Assert::assertSame('Block 2', $blocks[1]->getName());
        $codes2 = $blocks[1]->getCodes();
        Assert::assertCount(1, $codes2);
        Assert::assertSame('Code 1', $codes2[0]->getName());
        Assert::assertSame([], $codes2[0]->getScripts());

        // Block 3
        Assert::assertSame('Block 3', $blocks[2]->getName());
        $codes3 = $blocks[2]->getCodes();
        Assert::assertCount(2, $codes3);

        Assert::assertSame('Code 2', $codes3[0]->getName());
        $scripts3_0 = $codes3[0]->getScripts();
        Assert::assertCount(1, $scripts3_0);
        Assert::assertSame('SELECT 1', $scripts3_0[0]->getRawSql());

        Assert::assertSame('Code 3', $codes3[1]->getName());
        $scripts3_1 = $codes3[1]->getScripts();
        Assert::assertCount(2, $scripts3_1);
        Assert::assertSame('SELECT 1', $scripts3_1[0]->getRawSql());
        Assert::assertSame('INSERT INTO `table` VALUES(1,2,3);', $scripts3_1[1]->getRawSql());
    }

    public function testPortDefaultValue(): void
    {
        $config = new Config([
            'authorization' => $this->getAuthorizationNodeExcept('port'),
            'parameters' => $this->getParametersNode(),
        ], new ConfigDefinition());

        // Default value: 2 hours
        Assert::assertSame(1433, $config->getPort());
    }

    public function testQueryLimitDefaultValue(): void
    {
        $config = new Config([
            'authorization' => $this->getAuthorizationNode(),
            'parameters' => [
                'blocks' => [
                    [
                        'name' => 'Block 1',
                        'codes' => [],
                    ],
                ],
            ],
        ], new ConfigDefinition());

        // Default value: 2 hours
        Assert::assertSame(7200, $config->getQueryTimeout());
    }

    /**
     * @dataProvider validConfigProvider
     */
    public function testValidConfig(array $config): void
    {
        new Config($config, new ConfigDefinition());
        $this->addToAssertionCount(1); // Assert no error
    }

    /**
     * @dataProvider invalidConfigProvider
     */
    public function testInvalidConfig(string $expectedMsg, array $config): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedMsg);
        new Config($config, new ConfigDefinition());
    }

    public function validConfigProvider(): array
    {
        return [
            'empty parameters.blocks' => [
                [
                    'authorization' => $this->getAuthorizationNode(),
                    'parameters' => [
                        'blocks' => [],
                    ],
                ],
            ],
            'empty parameters.blocks.codes' => [
                [
                    'authorization' => $this->getAuthorizationNode(),
                    'parameters' => [
                        'blocks' => [
                            [
                                'name' => 'Block 1',
                                'codes' => [],
                            ],
                        ],
                    ],
                ],
            ],
            'empty parameters.blocks.codes.script' => [
                [
                    'authorization' => $this->getAuthorizationNode(),
                    'parameters' => [
                        'blocks' => [
                            [
                                'name' => 'Block 1',
                                'codes' => [
                                    [
                                        'name' => 'Code 1',
                                        'script' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'complex' => [$this->getComplexValidConfig()],
        ];
    }

    public function invalidConfigProvider(): array
    {
        return [
            'missing authorization' => [
                'The child node "authorization" at path "root" must be configured.',
                ['parameters' => $this->getParametersNode()],
            ],
            'empty authorization' => [
                'The child node "workspace" at path "root.authorization" must be configured.',
                ['authorization' => [], 'parameters' => $this->getParametersNode()],
            ],
            'missing authorization.workspace.host' => [
                'The child node "host" at path "root.authorization.workspace" must be configured.',
                [
                    'authorization' => $this->getAuthorizationNodeExcept('host'),
                    'parameters' => $this->getParametersNode(),
                ],
            ],
            'missing authorization.workspace.user' => [
                'The child node "user" at path "root.authorization.workspace" must be configured.',
                [
                    'authorization' => $this->getAuthorizationNodeExcept('user'),
                    'parameters' => $this->getParametersNode()],
                ],
            'missing authorization.workspace.password' => [
                'The child node "password" at path "root.authorization.workspace" must be configured.',
                [
                    'authorization' => $this->getAuthorizationNodeExcept('password'),
                    'parameters' => $this->getParametersNode(),
                ],
            ],
            'missing authorization.workspace.database' => [
                'The child node "database" at path "root.authorization.workspace" must be configured.',
                [
                    'authorization' => $this->getAuthorizationNodeExcept('database'),
                    'parameters' => $this->getParametersNode(),
                ],
            ],
            'authorization.workspace.port must be integer' => [
                'Invalid type for path "root.authorization.workspace.port". Expected int, but got string.',
                [
                    'authorization' => [
                        'workspace' => [
                            'host' => 'synapse.db',
                            'port' => '1234',
                            'user' => 'test-user',
                            'password' => 'test-password',
                            'database' => 'my-database',
                        ],
                    ],
                    'parameters' => $this->getParametersNode(),
                ],
            ],
            'missing parameters' => [
                'The child node "parameters" at path "root" must be configured.',
                [
                    'authorization' => $this->getAuthorizationNode(),
                ],
            ],
            'empty parameters (missing blocks)' => [
                'The child node "blocks" at path "root.parameters" must be configured.',
                [
                    'authorization' => $this->getAuthorizationNode(),
                    'parameters' => [],
                ],
            ],
            'missing parameters.blocks.codes' => [
                'The child node "codes" at path "root.parameters.blocks.0',
                [
                    'authorization' => $this->getAuthorizationNode(),
                    'parameters' => [
                        'blocks' => [
                            [
                                'name' => 'Block 1',
                            ],
                        ],
                    ],
                ],
            ],
            'missing parameters.blocks.name' => [
                'The child node "name" at path "root.parameters.blocks.0" must be configured.',
                [
                    'authorization' => $this->getAuthorizationNode(),
                    'parameters' => [
                        'blocks' => [
                            [
                                'codes' => [],
                            ],
                        ],
                    ],
                ],
            ],
            'missing parameters.blocks.codes.script' => [
                'The child node "script" at path "root.parameters.blocks.0.codes.0" must be configured.',
                [
                    'authorization' => $this->getAuthorizationNode(),
                    'parameters' => [
                        'blocks' => [
                            [
                                'name' => 'Block 1',
                                'codes' => [
                                    [
                                        'name' => 'Code 1',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'missing parameters.blocks.codes.name' => [
                'The child node "name" at path "root.parameters.blocks.0.codes.0" must be configured.',
                [
                    'authorization' => $this->getAuthorizationNode(),
                    'parameters' => [
                        'blocks' => [
                            [
                                'name' => 'Block 1',
                                'codes' => [
                                    [
                                        'script' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'parameters.blocks.codes.script must be array' => [
                'Invalid type for path "root.parameters.blocks.0.codes.0.script',
                [
                    'authorization' => $this->getAuthorizationNode(),
                    'parameters' => [
                        'blocks' => [
                            [
                                'name' => 'Block 1',
                                'codes' => [
                                    [
                                        'name' => 'Code 1',
                                        'script' => 'SELECT 1',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'parameters.query_limit must be integer' => [
                'Invalid type for path "root.parameters.query_timeout". Expected int, but got string.',
                [
                    'authorization' => $this->getAuthorizationNode(),
                    'parameters' => [
                        'query_timeout' => '1000',
                        'blocks' => [],
                    ],
                ],
            ],
        ];
    }

    private function getComplexValidConfig(): array
    {
        return [
            'authorization' => $this->getAuthorizationNode(),
            'parameters' => [
                'query_timeout' => 1000,
                'blocks' => [
                    [
                        'name' => 'Block 1',
                        'codes' => [],
                    ],
                    [
                        'name' => 'Block 2',
                        'codes' => [
                            [
                                'name' => 'Code 1',
                                'script' => [],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Block 3',
                        'codes' => [
                            [
                                'name' => 'Code 2',
                                'script' => [
                                    'SELECT 1',
                                ],
                            ],
                            [
                                'name' => 'Code 3',
                                'script' => [
                                    'SELECT 1',
                                    'INSERT INTO `table` VALUES(1,2,3);',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getAuthorizationNode(): array
    {
        return [
            'workspace' => [
                'host' => 'synapse.db',
                'port' => 1234,
                'user' => 'test-user',
                'password' => 'test-password',
                'database' => 'my-database',
            ],
        ];
    }

    private function getAuthorizationNodeExcept(string $except): array
    {
        $data = $this->getAuthorizationNode();

        if ($except) {
            unset($data['workspace'][$except]);
        }

        return $data;
    }

    private function getParametersNode(): array
    {
        return [
            'blocks' => [
                [
                  'name' => 'Block 1',
                  'codes' => [
                      [
                          'name' => 'Code 1',
                          'script' => [
                              'SELECT 1',
                          ],
                      ],
                  ],
                ],
            ],
        ];
    }
}
