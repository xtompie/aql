<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Xtompie\Aql\AqlService;

class AqlTest extends TestCase
{

    protected function aql(array $aql, string $sql, array $binds = [], array $types = [])
    {
        // given

        // when
        $result = (new AqlService())($aql);

        // then
        $this->assertSame($sql, $result->sql());
        $this->assertSame($binds, $result->binds());
        $this->assertSame($types, $result->types());
    }

    public function test_select_raw()
    {
        $this->aql(
            ["select" => "post_id, post_title as title"],
            "SELECT post_id, post_title as title",
        );
    }

    public function test_select_array()
    {
        $this->aql(
            ["select" => ['post_id', 'title' => 'post_title']],
            "SELECT `post_id`, `post_title` as 'title'",
        );
    }

    public function test_select_escape()
    {
        $this->aql(
            ["select" => ['count' => '|COUNT(*)']],
            "SELECT COUNT(*) as 'count'",
        );
    }

    public function test_select_escape2()
    {
        $this->aql(
            ["select" => ['|count' => '|COUNT(*)']],
            "SELECT COUNT(*) as count",
        );
    }

    public function test_prefix()
    {
        $this->aql(
            ["prefix" => 'SQL_NO_CACHE DISTINCT'],
            "SELECT SQL_NO_CACHE DISTINCT",
        );
    }

    public function test_from_simple()
    {
        $this->aql(
            ["from" => 'order'],
            "FROM `order`",
        );
    }

    public function test_from_alias()
    {
        $this->aql(
            ["from" => ['p' => 'posts']],
            "FROM `posts` as 'p'",
        );
    }

    public function test_join()
    {
        $this->aql(
            ['join' => ['JOIN author ON (author_id = post_id_author)', 'LEFT JOIN img ON (author_id_img = img_id)']],
            "JOIN author ON (author_id = post_id_author) LEFT JOIN img ON (author_id_img = img_id)",
        );
    }

    public function test_group()
    {
        $this->aql(
            ['group'  => 'post_id'],
            "GROUP BY post_id",
        );
    }

    public function test_having()
    {
        $this->aql(
            ['having' => 'post_id > 0'],
            "HAVING post_id > 0",
        );
    }

    public function test_having2()
    {
        $this->aql(
            ['having' => ['post_id >' =>  '0']],
            "HAVING `post_id` > ?",
            ['0'],
            ['string']
        );
    }

    public function test_order()
    {
        $this->aql(
            ['order' => 'created_at DESC'],
            "ORDER BY created_at DESC",
        );
    }

    public function test_offet()
    {
        $this->aql(
            ['offset' => 100],
            "OFFSET ?",
            [100],
            ['int'],
        );
    }

    public function test_limit()
    {
        $this->aql(
            ['limit' => 10],
            "LIMIT ?",
            [10],
            ['int']
        );
    }

    public function test_offset_limit()
    {
        $this->aql(
            ['offset' => 100, 'limit' => 10],
            "LIMIT ? OFFSET ?",
            [10, 100],
            ['int', 'int']
        );
    }

    public function test_equal()
    {
        $this->aql(
            ['where' => ['post_level' => 'a']],
            "WHERE `post_level` = ?",
            ['a'],
            ['string']
        );
    }

    public function test_in()
    {
        $this->aql(
            ['where' => ['post_level IN' => ['a', 'b', 'c']]],
            "WHERE `post_level` IN (?,?,?)",
            ['a', 'b', 'c'],
            ['string', 'string', 'string']
        );
    }

    public function test_in2()
    {
        $this->aql(
            ['where' => ['post_level:in' => ['1','2','3']]],
            "WHERE `post_level` IN (?,?,?)",
            ['1', '2', '3'],
            ['string', 'string', 'string']
        );
    }

    public function test_between()
    {
        $this->aql(
            ['where' => ['post_level BETWEEN' => [4, 5]]],
            "WHERE `post_level` BETWEEN ? AND ?",
            [4, 5],
            ['int', 'int']
        );
    }

    public function test_between2()
    {
        $this->aql(
            ['where' => ['post_level BETWEEN' => ['4', '5']]],
            "WHERE `post_level` BETWEEN ? AND ?",
            ['4', '5'],
            ['string', 'string']
        );
    }

    public function test_between3()
    {
        $this->aql(
            ['where' => ['post_level:between' => [4, 5]]],
            "WHERE `post_level` BETWEEN ? AND ?",
            [4, 5],
            ['int', 'int']
        );
    }

    public function test_not_between()
    {
        $this->aql(
            ['where' => ['post_level NOT BETWEEN' => [4, 5]]],
            "WHERE `post_level` NOT BETWEEN ? AND ?",
            [4, 5],
            ['int', 'int']
        );
    }

    public function test_not_equal()
    {
        $this->aql(
            ['where' => ['post_level !=' => 'a']],
            "WHERE `post_level` != ?",
            ['a'],
            ['string']
        );
    }

    public function test_not_equal2()
    {
        $this->aql(
            ['where' => ['post_level:not' => 'a']],
            "WHERE `post_level` != ?",
            ['a'],
            ['string']
        );
    }

    public function test_not_equal3()
    {
        $this->aql(
            ['where' => ['post_level:neq' => 'a']],
            "WHERE `post_level` != ?",
            ['a'],
            ['string']
        );
    }

    public function test_raw_key()
    {
        $this->aql(
            ['where' => ['|post_level' => 'a']],
            "WHERE post_level = ?",
            ['a'],
            ['string']
        );
    }

    public function test_or()
    {
        $this->aql(
            ['where' => [
                ':operator' => 'OR',
                'post_level' => 'a',
                'post_owner' => 'John',
            ]],
            "WHERE `post_level` = ? OR `post_owner` = ?",
            ['a', 'John'],
            ['string', 'string']
        );
    }

    public function test_parenteses()
    {
        $this->aql(
            ['where' => [
                'post_level' => 'a',
                [
                    ':operator' => 'OR',
                    'post_owner' => 'John',
                    'post_status' => 'draft',
                ]
            ]],
            "WHERE `post_level` = ? AND (`post_owner` = ? OR `post_status` = ?)",
            ['a', 'John', 'draft'],
            ['string', 'string', 'string']
        );
    }

    public function test_escape_null()
    {
        $this->aql(
            ['where' => [
                'post_level' => null,
            ]],
            "WHERE `post_level` = ?",
            [null],
            ['null']
        );
    }

    public function test_escape_int()
    {
        $this->aql(
            ['where' => [
                'post_level' => 12,
            ]],
            "WHERE `post_level` = ?",
            [12],
            ['int']
        );
    }

    public function test_escape_string()
    {
        $this->aql(
            ['where' => [
                'post_level' => 'a',
            ]],
            "WHERE `post_level` = ?",
            ['a'],
            ['string']
        );
    }
}
