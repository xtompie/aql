<?php

declare(strict_types=1);

namespace Xtompie\Aql;

use Exception;

class AqlResult
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

    public function types(): array
    {
        $types = [];
        foreach ($this->binds as $bind) {
            if (is_null($bind)) {
                $types[] = 'null';
            }
            else if (is_bool($bind)) {
                $types[] = 'bool';
            }
            else if (is_int($bind)) {
                $types[] = 'int';
            }
            else if (is_string($bind)) {
                $types[] = 'string';
            }
            else {
                throw new Exception("Unsupported bind type. Variable type '" . gettype($bind) . "'. Supported bind types: null, bool, int, string.");
            }
        }

        return $types;
    }

    public function toArray(): array
    {
        return [
            $this->sql(),
            $this->binds(),
            $this->types(),
        ];
    }
}
