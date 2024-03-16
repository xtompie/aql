<?php

declare(strict_types=1);

namespace Xtompie\Aql;

interface Platform
{
    public function quoteIdentifier(string $identifier): string;
}