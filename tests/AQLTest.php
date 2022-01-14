<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Xtompie\AQL\AQL;


class AQLTest extends TestCase
{
    protected $escaper;

    protected function setUp(): void
    {
        $this->escaper = fn ($s) => addslashes($s);
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
}

 /**
     * Builds fragmenet or full raw sql query
     *
     * $aql options:
     * - 'group'  => 'post_id'
     * - 'having' => 'post_id > 0'
     * - 'having' => ['post_id >' =>  '0']
     * - 'order'  => 'post_published DESC'
     * - 'limit'  => 10,
     * - 'offset' => 0,
     * - 'where' => []
     *   - 'post_level' => [1, 2, 3] // `post_level` IN ('1', '2', '3')
     *   - 'post_level BETWEEN' => [4, 5] // `post_level` BETWEEN '4' AND '5'
     *   - 'post_level <>' => 4 // `post_level` <> '4'
     *   - '|post_level <>' => 4 // post_level <> '4'
     *   - "|post_level != '{a}'" => ['{a}' => 4] // post_level != '4'
     *   - ':operator' => 'AND' // values: AND, OR; default: AND; logical operator that joins all conditions
     *   - [':operator' => 'OR', 'post_level' => '1'
     *      ['operator' => 'OR', 'post_level' => '2', 'post_level' => '3']]
     *     // post_level = '1' OR (post_level = '2' OR  post_level = '3')
     *
     * @param array $aql
     * @return string SQL statement
     */