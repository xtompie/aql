<?php

declare(strict_types=1);

namespace Xtompie\Aql;

class AqlBuild
{
    public function __construct(
        protected string $sql = '',
        protected array $binds = [],
    ) {}

    public function sql(string $sql, string $space = '')
    {
        $this->sql .= $sql;
    }

    public function sqls(array $sqls, string $glue = ' ', $space = ' ')
    {
        $this->sql .= $space . implode($glue, $sqls);
    }

    public function bind(mixed $value): string
    {
        $this->binds[] = $value;
        return "?";
    }

    public function types(): array
    {
        return $this->binds;
    }

    public function result(): AqlResult
    {
        return new AqlResult(trim($this->sql), $this->binds);
    }
}
