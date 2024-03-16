# Array Query Language

- [Array Query Language](#array-query-language)
  - [Requiments](#requiments)
  - [Installation](#installation)
  - [Docs](#docs)
    - [Api](#api)
      - [Select](#select)
      - [Prefix](#prefix)
      - [From](#from)
      - [Join](#join)
      - [Group](#group)
      - [Having](#having)
      - [Order](#order)
      - [Limit](#limit)
      - [Offset](#offset)
      - [Where](#where)
        - [String key](#string-key)
        - [Int key and string value](#int-key-and-string-value)
        - [Int key and array value](#int-key-and-array-value)
      - [Insert](#insert)
    - [Platform](#platform)
    - [Extending](#extending)

Building SQL prepared statment with binds using array

```php
use Xtompie\Aql\Aql;
use Xtompie\Aql\MySQLPlatform;

$aql = new Aql(
    platform: new MySQLPlatform(),
);
$result = $aql([
    'select' => '*',
    'from' => 'order',
    'where' => [
        'status' => 'active',
    ],
    'limit' => 3,
]);
$result->sql(); // 'SELECT * FROM `order` WHERE status = ? LIMIT 3'
$result->binds(); // ['active']
$result->toArray(); // ['SELECT * FROM `order` WHERE status = ? LIMIT 3', ['active']]
```

## Requiments

PHP >= 8.0

## Installation

Using [composer](https://getcomposer.org/)

```shell
composer require xtompie/aql
```

## Docs

### Api

#### Select

```php
$aql(['select' => 'post_id', 'title' => 'post_title'])->toArray();
// ["SELECT post_id, post_title as 'title'", []];

$aql(['select' => 'post_id, post_title as title'])->toArray();
// ['SELECT post_id, post_title as title', []];

$aql(['select' => '|x' => '|COUNT(*)'])->toArray();
// ['SELECT COUNT(*) as x', []];
```

The `|` character can be specified at the beginning of key or value to use the raw sql fragment

#### Prefix

```php
$aql(['prefix' => 'SQL_NO_CACHE DISTINCT'])->toArray();
// ['SELECT SQL_NO_CACHE DISTINCT', []];
```

#### From

```php
$aql(['from' => 'user'])->toArray();
// ['FROM user', []];

$aql(['from' => ['u' => 'user']])->toArray();
// ['FROM user as u', []];

$aql(['from' => 'order'])->toArray();
// ['FROM `order`', []];
```

Keywords are quoted.

#### Join

```php
$aql([
    'join' => [
        'JOIN author ON (author_id = post_id_author)',
        'LEFT JOIN img ON (author_id_img = img_id)'
    ]
])->toArray();
// ['JOIN author ON (author_id = post_id_author) LEFT JOIN img ON (author_id_img = img_id)"]
```

#### Group

```php
$aql(['group' => 'post_id'])->toArray();
// ['GROUP post_id', []];
```

#### Having

```php
$aql(['having' => 'post_id > 0'])->toArray();
// ['HAVING post_id > 0', []];

$aql(['having' => ['post_id >' => '0']])->toArray();
// ['HAVING post_id > ?', [0]];
```

Array of conditions can be set as having.
It behaves as where conditions.
See [Where](#where).

#### Order

```php
$aql(['order' => 'created_at DESC'])->toArray();
// ['ORDER BY created_at DESC', []];
```

Order is a raw sql fragment.

#### Limit

```php
$aql(['limit' => '10'])->toArray();
// ['LIMIT ?', [10]];
```

Limit is casted to int.

#### Offset

```php
$aql(['offset' => '20'])->toArray();
// ['OFFSET ?', [20]];
```

Offset is casted to int.

#### Where

##### String key

```php
$aql([
    'where' => [
        'a' => 'a',
        'b' => ['b1', 'b2', 'b3'],
        'c BETWEEN' => [2, 5],
        'd <>' => 'd1',
        'e LIKE' => '%e1%',
        'f:gt' => 9,
    ]
])
    ->toArray()
;
// [
//    'WHERE a = ? AND b IN (?, ?, ?) AND c BETWEEN ? AND ? AND d <> ? AND e LIKE ? AND f > ?',
//    ['a', 'b1', 'b2', 'b3', 2, 5, 'd1', '%e1%', 9]
// ];
```

When condition key is a string then expected is column name with optional comparison operator.
Compartition operator is expected after first space or `:` character.
Available compartition operators are all valid SQL comparition operators and aditional:

`eq` is `=`,
`gt` is `>`,
`ge` is `>=`,
`lt` is `<`,
`le` is `<=`,
`not`, `neq` is `!=`,
`like` is `LIKE`,
`in` is `IN`,
`notin` is `NOT IN`,
`between` is `BETWEEN`,
`notbetween` is `NOT BETWEEN`,

The `|` character can be specified at the beginning of key to use the raw sql fragment.

By default logical operator for all condition is `AND`.
Logical operator can by change using `:operator` key.

```php
$aql([
    'where' => [
        'a' => 'a',
        'b' => 'b',
        ':operator' => 'OR',
    ]
])
    ->toArray()
;
// [
//    'WHERE a = ? OR b = ?',
//    ['a', 'bb']
// ];
```

##### Int key and string value

```php
$aql(['where' => ['category_id IS NOT NULL']])->toArray();
// ['WHERE category_id IS NOT NULL', []];
```

##### Int key and array value

```php
$aql([
    'where' => [
        'a' => 'aa',
        [
            'b' => 'bb',
            'c' => 'cc',
            ':operator' => 'OR',
        ]
    ]
])->toArray();
// ['WHERE a = ? AND (b = ? OR c = ?)', ['aa', 'bb', 'cc]];
```

#### Insert

```php
$aql([
    'insert' => 'order',
    'values' => [
        'order' => 1,
        '|time' => 'NOW()',
    ]
])->toArray();
// ['INSERT INTO `order` (`order`, time) VALUES (?, NOW())', [1]];
```

### Platform

Build in supported platforms:

- `Xtompie/Aql/MySQLPlatform`,
- `Xtompie/Aql/PostgreSQLPlatform`.
- `Xtompie/Aql/SQLitePlatform`.

Using `PostgreSQL`:

```php
use Xtompie/Aql/Aql;
use Xtompie/Aql/PostgreSQLPlatform;

(new Aql(platform: new PostgreSQLPlatform()))([
    'SELECT' => '*',
    'FROM' => 'order'
])->toArray();
// ['SELECT * FROM "order"', []];
```

### Extending

By decorating

```php
<?php

namespace App\Shared\Database;

use Xtompie\Aql\Aql as BaseAql;
use Xtompie\Aql\Result;

interface Paging
{
    public function limit(): int;
    public function offset(): int;
}

class Aql
{
    public function __construct(
        protected BaseAql $aql,
    ) {}

    public function __invoke(array $aql): Result
    {
        if (isset($aql['paging'])) {
            $paging = $aql['paging'];
            if (!$paging instanceof Paging) {
                throw new \Exception();
            }
            $aql['offset'] => $paging->offset();
            $aql['limit'] => $paging->limit();
            unset($aql['paging']);
        }
        return ($this->aql)($aql);
    }
}

```
