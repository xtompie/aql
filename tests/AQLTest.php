<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Xtompie\AQL\AQL;


class AQLTest extends TestCase
{
    protected $escaper;

    protected function setUp(): void
    {
        $this->escaper = function ($v) {
            if (is_null($v)) {
                return 'NULL';
            }
            if (is_integer($v)) {
                return $v;
            }
            return addslashes($v);
        };
        $this->service = new AQL();
    }

    protected function test($sql, $aql, $mehtod)
    {
        // given

        // when
        $result = $this->service->{$mehtod}($aql, $this->escaper);

        // then
        $this->assertSame($sql, $result);
    }

    protected function testQuery($sql, $aql)
    {
        $this->test($sql, $aql, 'query');
    }

    protected function testCommand($sql, $aql)
    {
        $this->test($sql, $aql, 'query');
    }

    protected function testCondition($sql, $aql)
    {
        $this->test($sql, $aql, 'condition');
    }

    public function test_select_raw()
    {
        $this->testQuery(
            "SELECT post_id, post_title as title",
            ["select" => "post_id, post_title as title"],
        );
    }

    public function test_select_array()
    {
        $this->testQuery(
            "SELECT `post_id`, `post_title` as 'title'",
            ["select" => ['post_id', 'title' => 'post_title']],
        );
    }

    public function test_select_escape()
    {
        $this->testQuery(
            "SELECT COUNT(*) as 'count'",
            ["select" => ['count' => '|COUNT(*)']],
        );
    }

    public function test_select_escape2()
    {
        $this->testQuery(
            "SELECT COUNT(*) as count",
            ["select" => ['|count' => '|COUNT(*)']],
        );
    }

    public function test_prefix()
    {
        $this->testQuery(
            "SELECT SQL_NO_CACHE DISTINCT",
            ["prefix" => 'SQL_NO_CACHE DISTINCT'],
        );
    }

    public function test_from_simple()
    {
        $this->testQuery(
            "FROM `order`",
            ["from" => 'order'],
        );
    }

    public function test_from_alias()
    {
        $this->testQuery(
            "FROM `posts` as 'p'",
            ["from" => ['p' => 'posts']],
        );
    }

    public function test_join()
    {
        $this->testQuery(
            "JOIN author ON (author_id = post_id_author) LEFT JOIN img ON (author_id_img = img_id)",
            ['join'  => ['JOIN author ON (author_id = post_id_author)', 'LEFT JOIN img ON (author_id_img = img_id)']],
        );
    }

    public function test_group()
    {
        $this->testQuery(
            "GROUP BY post_id",
            ['group'  => 'post_id'],
        );
    }

    public function test_having()
    {
        $this->testQuery(
            "HAVING post_id > 0",
            ['having' => 'post_id > 0'],
        );
    }

    public function test_having2()
    {
        $this->testQuery(
            "HAVING `post_id` > '0'",
            ['having' => ['post_id >' =>  '0']],
        );
    }

    public function test_order()
    {
        $this->testQuery(
            "ORDER BY created_at DESC",
            ['order' => 'created_at DESC'],
        );
    }

    public function test_offet()
    {
        $this->testQuery(
            "OFFSET 100",
            ['offset' => 100],
        );
    }

    public function test_limit()
    {
        $this->testQuery(
            "LIMIT 10",
            ['limit' => 10],
        );
    }

    public function test_offset_limit()
    {
        $this->testQuery(
            "LIMIT 10 OFFSET 100",
            ['offset' => 100, 'limit' => 10],
        );
    }

    public function test_equal()
    {
        $this->testQuery(
            "WHERE `post_level` = 'a'",
            ['where' => ['post_level' => 'a']],
        );
    }

    public function test_in()
    {
        $this->testQuery(
            "WHERE `post_level` IN ('a','b','c')",
            ['where' => ['post_level IN' => ['a','b','c']]],
        );
    }

    public function test_in2()
    {
        $this->testQuery(
            "WHERE `post_level` IN ('1','2','3')",
            ['where' => ['post_level:in' => ['1','2','3']]],
        );
    }

    public function test_between()
    {
        $this->testQuery(
            "WHERE `post_level` BETWEEN 4 AND 5",
            ['where' => ['post_level BETWEEN' => [4, 5]]],
        );
    }

    public function test_between2()
    {
        $this->testQuery(
            "WHERE `post_level` BETWEEN '4' AND '5'",
            ['where' => ['post_level BETWEEN' => ['4', '5']]],
        );
    }

    public function test_between3()
    {
        $this->testQuery(
            "WHERE `post_level` BETWEEN 4 AND 5",
            ['where' => ['post_level:between' => [4, 5]]],
        );
    }

    public function test_not_between()
    {
        $this->testQuery(
            "WHERE `post_level` NOT BETWEEN 4 AND 5",
            ['where' => ['post_level NOT BETWEEN' => [4, 5]]],
        );
    }

    public function test_not_equal()
    {
        $this->testQuery(
            "WHERE `post_level` != 'a'",
            ['where' => ['post_level !=' => 'a']],
        );
    }

    public function test_not_equal2()
    {
        $this->testQuery(
            "WHERE `post_level` != 'a'",
            ['where' => ['post_level:not' => 'a']],
        );
    }

    public function test_not_equal3()
    {
        $this->testQuery(
            "WHERE `post_level` != 'a'",
            ['where' => ['post_level:neq' => 'a']],
        );
    }

    public function test_raw_key()
    {
        $this->testQuery(
            "WHERE post_level = 'a'",
            ['where' => ['|post_level' => 'a']],
        );
    }

    public function test_replace()
    {
        $this->testQuery(
            "WHERE post_owner = 'Tomek\'s' AND post_level = 'x'",
            ['where' => [
                "|post_owner = {owner} AND post_level = {level}" =>
                [
                    '{owner}' => 'Tomek\'s',
                    '{level}' => 'x',
                ]
            ]],
        );
    }
    public function test_or()
    {
        $this->testQuery(
            "WHERE `post_level` = 'a' OR `post_owner` = 'John'",
            ['where' => [
                ':operator' => 'OR',
                'post_level' => 'a',
                'post_owner' => 'John',
            ]],
        );
    }
    public function test_parenteses()
    {
        $this->testQuery(
            "WHERE `post_level` = 'a' AND (`post_owner` = 'John' OR `post_status` = 'draft')",
            ['where' => [
                'post_level' => 'a',
                [
                    ':operator' => 'OR',
                    'post_owner' => 'John',
                    'post_status' => 'draft',
                ]
            ]],
        );
    }
}
