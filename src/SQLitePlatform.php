<?php

declare(strict_types=1);

namespace Xtompie\Aql;

class SQLitePlatform implements Platform
{
    protected array $keywords = [];

    public function quoteIdentifier(string $identifier): string
    {
        if (str_contains($identifier, '.')) {
            $parts = array_map($this->quoteSingleIdentifier(...), explode('.', $identifier));

            return implode('.', $parts);
        }

        return $this->quoteSingleIdentifier($identifier);
    }

    protected function quoteSingleIdentifier(string $identifier): string
    {
        if (empty($this->keywords)) {
            $this->initializeKeywords();
        }

        if (!isset($this->keywords[strtoupper($identifier)])) {

            return $identifier;
        }

        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    protected function initializeKeywords()
    {
        $this->keywords = array_flip([
            'ABORT',
            'ACTION',
            'ADD',
            'AFTER',
            'ALL',
            'ALTER',
            'ANALYZE',
            'AND',
            'AS',
            'ASC',
            'ATTACH',
            'AUTOINCREMENT',
            'BEFORE',
            'BEGIN',
            'BETWEEN',
            'BY',
            'CASCADE',
            'CASE',
            'CAST',
            'CHECK',
            'COLLATE',
            'COLUMN',
            'COMMIT',
            'CONFLICT',
            'CONSTRAINT',
            'CREATE',
            'CROSS',
            'CURRENT_DATE',
            'CURRENT_TIME',
            'CURRENT_TIMESTAMP',
            'DATABASE',
            'DEFAULT',
            'DEFERRABLE',
            'DEFERRED',
            'DELETE',
            'DESC',
            'DETACH',
            'DISTINCT',
            'DROP',
            'EACH',
            'ELSE',
            'END',
            'ESCAPE',
            'EXCEPT',
            'EXCLUSIVE',
            'EXISTS',
            'EXPLAIN',
            'FAIL',
            'FOR',
            'FOREIGN',
            'FROM',
            'FULL',
            'GLOB',
            'GROUP',
            'HAVING',
            'IF',
            'IGNORE',
            'IMMEDIATE',
            'IN',
            'INDEX',
            'INDEXED',
            'INITIALLY',
            'INNER',
            'INSERT',
            'INSTEAD',
            'INTERSECT',
            'INTO',
            'IS',
            'ISNULL',
            'JOIN',
            'KEY',
            'LEFT',
            'LIKE',
            'LIMIT',
            'MATCH',
            'NATURAL',
            'NO',
            'NOT',
            'NOTNULL',
            'NULL',
            'OF',
            'OFFSET',
            'ON',
            'OR',
            'ORDER',
            'OUTER',
            'PLAN',
            'PRAGMA',
            'PRIMARY',
            'QUERY',
            'RAISE',
            'REFERENCES',
            'REGEXP',
            'REINDEX',
            'RELEASE',
            'RENAME',
            'REPLACE',
            'RESTRICT',
            'RIGHT',
            'ROLLBACK',
            'ROW',
            'SAVEPOINT',
            'SELECT',
            'SET',
            'TABLE',
            'TEMP',
            'TEMPORARY',
            'THEN',
            'TO',
            'TRANSACTION',
            'TRIGGER',
            'UNION',
            'UNIQUE',
            'UPDATE',
            'USING',
            'VACUUM',
            'VALUES',
            'VIEW',
            'VIRTUAL',
            'WHEN',
            'WHERE',
        ]);
    }
}
