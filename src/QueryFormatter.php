<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation;

class QueryFormatter
{
    public function removeComments(string $rawSql): string
    {
        return trim(\SqlFormatter::removeComments($rawSql));
    }

    public function formatToLog(string $sql): string
    {
        // Remove new lines and indentation (logger removes line breaks)
        $sql = preg_replace('~\s*\n+\s*~', ' ', $sql);
        assert(is_string($sql));

        // Shorten
        if (mb_strlen($sql) > 1000) {
            return
                mb_substr($sql, 0, 500, 'UTF-8') .
                "\n...\n" .
                mb_substr($sql, -500, null, 'UTF-8');
        }

        return $sql;
    }
}
