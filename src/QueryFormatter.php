<?php

declare(strict_types=1);

namespace Keboola\SynapseTransformation;

use SqlFormatter;

class QueryFormatter
{
    public function __construct()
    {
        // Synapse supports "--" for line comment, and "/**/" for block comment.
        // Char "#" means temp table, so it must not be used.
        // https://docs.microsoft.com/en-us/sql/t-sql/language-elements/comment-transact-sql?view=sql-server-ver15
        // https://docs.microsoft.com/en-us/sql/t-sql/language-elements/slash-star-comment-transact-sql?view=sql-server-ver15
        SqlFormatter::setBoundaries(array_diff(SqlFormatter::DEFAULT_BOUNDARIES, ['#']));
        SqlFormatter::$comment_tokens = [
            ['--'],
            ['/*', '*/'],
        ];
    }

    public function removeComments(string $rawSql): string
    {
        return trim(SqlFormatter::removeComments($rawSql, false));
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
