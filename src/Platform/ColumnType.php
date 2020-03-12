<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation\Platform;

use Keboola\Datatype\Definition\BaseType;
use Keboola\Datatype\Definition\Common;
use Keboola\SynapseTransformation\Exception\NotImplementedException;
use Keboola\SynapseTransformation\Exception\UnexpectedColumnType;

class ColumnType extends Common
{
    // https://docs.microsoft.com/en-us/sql/t-sql/data-types/data-types-transact-sql?view=sql-server-ver15
    public const TYPES_MAPPING = [
        // BOOLEAN
        'bit'              => BaseType::BOOLEAN,
        // INTEGER
        'bigint'           => BaseType::INTEGER,
        'int'              => BaseType::INTEGER,
        'smallint'         => BaseType::INTEGER,
        'tinyint'          => BaseType::INTEGER,
        // NUMERIC
        'decimal'          => BaseType::NUMERIC,
        'double'           => BaseType::NUMERIC,
        'double precision' => BaseType::NUMERIC,
        'float'            => BaseType::NUMERIC,
        'money'            => BaseType::NUMERIC,
        'numeric'          => BaseType::NUMERIC,
        'real'             => BaseType::NUMERIC,
        'smallmoney'       => BaseType::NUMERIC,
        // DATE
        'date'             => BaseType::DATE,
        'datetime'         => BaseType::DATE,
        'datetime2'        => BaseType::DATE,
        'datetimeoffset'   => BaseType::DATE,
        'smalldatetime'    => BaseType::DATE,
        // STRING
        'binary'           => BaseType::STRING,
        'char'             => BaseType::STRING,
        'image'            => BaseType::STRING,
        'nchar'            => BaseType::STRING,
        'ntext'            => BaseType::STRING,
        'nvarchar'         => BaseType::STRING,
        'text'             => BaseType::STRING,
        'time'             => BaseType::STRING,
        'uniqueidentifier' => BaseType::STRING,
        'varbinary'        => BaseType::STRING,
        'varchar'          => BaseType::STRING,
    ];

    public function toArray(): array
    {
        throw new NotImplementedException();
    }

    public function getSQLDefinition(): string
    {
        throw new NotImplementedException();
    }


    public function getBasetype(): string
    {
        $nativeType = strtolower($this->type);
        if (isset(self::TYPES_MAPPING[$nativeType])) {
            return self::TYPES_MAPPING[$nativeType];
        }

        throw new UnexpectedColumnType(sprintf('Unexpected type "%s" for Synapse DB.', $nativeType));
    }
}
