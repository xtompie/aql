# Array Query Language

Building SQL using array


```php
use Xtompie\Aql\Aql;

$sql = Aql::query([
    "select" => "*",
    "from" => "users"
    "where" => [
        "status" => "active",
    ],
    "limit" => 3,
]);
```

## Requiments

PHP >= 8.0

## Installation

Using [composer](https://getcomposer.org/)

```
composer require xtompie/aql
```

## Docs

```
- 'select' => ['post_id', 'title' => 'post_title']  // `post_id`, `post_title` as 'title'
- 'select' => 'post_id, post_title as title' // post_id, post_title as title
- 'select' => ['|count' => '|COUNT(*)']  // COUNT(*) as count
- 'prefix' => 'SQL_NO_CACHE DISTINCT'
- 'from'   => 'post'
- 'from'   => ['p' => 'post']
- 'join'   => ['JOIN author ON (author_id = post_id_author)', 'LEFT JOIN img ON (author_id_img = img_id)']
- 'group'  => 'post_id'
- 'having' => 'post_id > 0'
- 'having' => ['post_id >' =>  '0']
- 'order'  => 'post_published DESC'
- 'limit'  => 10,
- 'offset' => 0,
- 'where' => []
  - 'post_level' => [1, 2, 3] // `post_level` IN ('1', '2', '3')
  - 'post_level BETWEEN' => [4, 5] // `post_level` BETWEEN '4' AND '5'
  - 'post_level <>' => 4 // `post_level` <> '4'
  - '|post_level <>' => 4 // post_level <> '4'
  - "|post_level != '{a}'" => ['{a}' => 4] // post_level != '4'
  - ':operator' => 'AND' // values: AND, OR; default: AND; logical operator that joins all conditions
  - [':operator' => 'OR', 'post_level' => '1', ['operator' => 'OR', 'post_level' => '2', 'post_level' => '3']]
    // post_level = '1' OR (post_level = '2' OR  post_level = '3')
```