<?php

declare(strict_types=1);

namespace Xtompie\Aql;

class Build
{
    public function __construct(
        protected string $sql = '',
        protected array $binds = [],
    ) {}

    public function sql(string $sql, string $space = '')
    {
        $this->sql .= $space . $sql;
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

    public function result(): Result
    {
        return new Result(trim($this->sql), $this->binds);
    }
}
