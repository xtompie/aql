<?php

declare(strict_types=1);

namespace Xtompie\AQL;

/**
 * Array Query Language - SQL from array
 */
class AQL
{
    /**
     * Builds fragmenet or full raw sql query
     *
     * $aql options:
     * - 'select' => ['post_id', 'title' => 'post_title']  // `post_id`, `post_title` as 'title'
     * - 'select' => 'post_id, post_title as title' // post_id, post_title as title
     * - 'select' => ['|count' => '|COUNT(*)']  // COUNT(*) as count
     * - 'prefix' => 'SQL_NO_CACHE DISTINCT'
     * - 'from'   => 'post'
     * - 'from'   => ['p' => 'post']
     * - 'join'   => ['JOIN author ON (author_id = post_id_author)', 'LEFT JOIN img ON (author_id_img = img_id)']
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
    public function query(array $aql, callable $escaper): string
    {
        return trim(
            $this->select($aql['select'] ?? null, $aql['prefix'] ?? null, $escaper)
            . $this->from($aql['from'] ?? null, $escaper)
            . $this->join($aql['join'] ?? null)
            . $this->where($aql['where'] ?? null, $escaper)
            . $this->group($aql['group'] ?? null)
            . $this->having($aql['having'] ?? null, $escaper)
            . $this->order($aql['order'] ?? null)
            . $this->limit($aql['limit'] ?? null)
            . $this->offset($aql['offset'] ?? null)
        );
    }

    public function condition(array|null $condition, callable $escaper): string
    {
        if ($condition === null) {
            return '';
        }

        $logical = ' AND ';
        $sql     = [];

        foreach ($condition as $key => $value) {

            // <int> => [], <int> => '<raw sql>'
            if (is_int($key)) {
                $sql[] = is_array($value) ? '(' . $this->condition($value, $escaper) . ')' : $value;
                continue;
            }

            // '|(a = {a} OR b = {b})' => [{a} => '1', {b} => '2']
            if ($key[0] === '|' && is_array($value)) {
                $key = substr($key, 1);
                foreach ($value as $find => $replace) {
                    $key = str_replace($find, $escaper($replace), $key);
                }
                $sql[] = $key;
                continue;
            }

            // logical operator - AND, OR
            if ($key === ':operator') {
                $logical = ' ' . trim($value) . ' ';
                continue;
            }

            // key and comparison
            [$key, $comparison] = array_pad(preg_split('/[:\s]/', $key, 2), 2, null);
            $key = $key[0] === '|' ? substr($key, 1) : "`$key`";
            $comparison = $comparison !== null ? $comparison : '=';

            switch ($comparison) {

                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $sql[] = "$key $comparison"
                             . " '{$escaper($value[0])}'"
                             . " AND '{$escaper($value[1])}'";
                    break;

                case 'IN':
                case 'NOT IN':
                    foreach ($value as $k => $v) {
                        $value[$k] = $escaper($v);
                    }
                    $sql[] = "$key $comparison ('" . implode("','", $value) . "')";
                    break;

                default:
                    $sql[] = "$key $comparison '{$escaper($value)}'";
                    break;

            }

        }

        return implode($logical, $sql);
    }

    public function command(array $aql, callable $escaper): string
    {
        return trim(
            $this->insert($aql['insert'] ?? null, $escaper)
            . $this->update($aql['update'] ?? null, $escaper)
            . $this->delete($aql['delete'] ?? null, $escaper)
            . $this->set($aql['set'] ?? null, $escaper)
            . $this->where($aql['where'] ?? null, $escaper)
        );
    }

    protected function insert(string|null $insert): string
    {
        if ($insert === null) {
            return '';
        }
        return "INSERT INTO `{$insert}`";
    }

    protected function update(string|null $update): string
    {
        if ($update === null) {
            return '';
        }
        return "UPDATE `{$update}`";
    }

    protected function delete(string|null $delete): string
    {
        if ($delete === null) {
            return '';
        }
        return "DELETE FROM `{$delete}`";
    }

    protected function select(array|string|null $select, array|string|null $prefix, callable $escaper): string
    {
        $sql = '';
        if ($select != null) {
            if (!is_array($select)) {
                $sql = $select;
            }
            else {
                $fields = [];
                foreach ($select as $k => $v) {
                    $field = $v[0] === '|' ? substr($v, 1) : "`$v`";
                    if (!is_int($k)) {
                        $field .= " as " . ($k[0] === '|' ? substr((string)$k, 1) : "'{$escaper((string)$k)}'");
                    }
                    $fields[] = $field;
                }
                $sql = implode(', ', $fields);
            }
        }
        if ($prefix != null) {
            $sql =   $prefix . ' ' . $sql;
        }
        if ($sql != '') {
            $sql = 'SELECT ' . $sql;
        }
        return $sql;
    }

    protected function from(array|string|null $from, callable $escaper): string
    {
        $sql = '';
        if ($from != null) {
            $sql = ' FROM ';
            if (is_array($from)) {
                $table = reset($from);
                $alias = key($from);
                $sql .= "`$table` as '{$escaper($alias)}'";
            }
            else if ($from[0] === '|') {
                $sql .= substr($from, 1);
            }
            else {
                $sql .= "`{$from}`";
            }
        }
        return $sql;
    }

    protected function join(array|null $join): string
    {
        if ($join === null) {
            return '';
        }
        return ' ' . implode(' ', $join);
    }

    protected function group(array|string|null $group): string
    {
        if ($group === null) {
            return '';
        }
        return ' GROUP BY ' . (is_array($group) ? implode(' ', $group) : $group);
    }

    protected function having(array|string|null $having, callable $escaper): string
    {
        if ($having === null) {
            return '';
        }
        return ' HAVING ' . (is_string($having) ? $having : $this->condition($having, $escaper));
    }

    protected function order(array|string|null $order): string
    {
        if ($order === null) {
            return '';
        }

        $order = (is_array($order) ? implode(', ', $order) : $order);

        return ' ORDER BY ' . $order;
    }

    protected function limit(string|int|null $limit): string
    {
        if ($limit === null) {
            return '';
        }
        return ' LIMIT ' . (string)$limit;
    }

    protected function offset(string|int|null $offset): string
    {
        if ($offset === null) {
            return '';
        }
        return ' OFFSET ' . (string)$offset;
    }

    protected function where(array|null $where, callable $escaper): string
    {
        $sql = $this->condition($where, $escaper);
        if ($sql === '') {
            return $sql;
        }

        return ' WHERE ' . $sql;
    }

    protected function set(array|null $set, callable $escaper): string
    {
        if ($set === null) {
            return '';
        }

        $sql = [];
        foreach ($set as $k => $v) {
            $sql[$k] = "`$k` = '{$escaper($v)}'";
        }

        return ' SET ' . implode(', ', $sql);
    }
}
