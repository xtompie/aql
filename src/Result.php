<?php

declare(strict_types=1);

namespace Xtompie\Aql;

class Result
{
    public function __construct(
        protected string $sql,
        protected array $binds,
    ) {}

    public function sql(): string
    {
        return $this->sql;
    }

    public function binds(): array
    {
        return $this->binds;
    }

    public function toArray(): array
    {
        return [
            $this->sql(),
            $this->binds(),
        ];
    }
}
