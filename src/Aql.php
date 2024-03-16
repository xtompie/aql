<?php

declare(strict_types=1);

namespace Xtompie\Aql;

use Exception;

class Aql
{
    public function __construct(
        protected ?Platform $platform = null,
    ) {
        if ($platform === null) {
            $this->platform = new MySQLPlatform();
        }
    }

    public function __invoke(array $aql): Result
    {
        $build = new Build();

        $this->select($aql, $build);
        $this->insert($aql, $build);
        $this->update($aql, $build);
        $this->delete($aql, $build);
        $this->from($aql, $build);
        $this->set($aql, $build);
        $this->values($aql, $build);
        $this->valuesBulk($aql, $build);
        $this->join($aql, $build);
        $this->where($aql, $build);
        $this->group($aql, $build);
        $this->having($aql, $build);
        $this->order($aql, $build);
        $this->limit($aql, $build);
        $this->offset($aql, $build);

        return $build->result();
    }

    protected function select(array $aql, Build $build)
    {
        if (!isset($aql['select']) && !isset($aql['prefix'])) {
            return;
        }

        $build->sql(' SELECT');

        if (isset($aql['prefix'])) {
            $build->sql(' ' . $aql['prefix']);
        }

        if (isset($aql['select']) && is_string($aql['select'])) {
            $build->sql(' ' . $aql['select']);
            return;
        }

        if (isset($aql['select']) && is_array($aql['select'])) {
            $fields = [];
            foreach ($aql['select'] as $k => $v) {
                $field = $v[0] === '|' ? substr($v, 1) : $this->quoteIdentifier($v);
                if (!is_int($k)) {
                    $field .= " as " . ($k[0] === '|' ? substr((string)$k, 1) : "'$k'");
                }
                $fields[] = $field;
            }
            $build->sqls($fields, ', ');
        }
    }

    protected function insert(array $aql, Build $build)
    {
        if (!isset($aql['insert'])) {
            return;
        }
        $build->sql(" INSERT INTO " . $this->quoteIdentifier($aql['insert']));
    }

    protected function update(array $aql, Build $build)
    {
        if (!isset($aql['update'])) {
            return;
        }
        $build->sql(" UPDATE " . $this->quoteIdentifier($aql['update']));
    }

    protected function delete(array $aql, Build $build)
    {
        if (!isset($aql['delete'])) {
            return;
        }
        $build->sql(" DELETE FROM " . $this->quoteIdentifier($aql['delete']));
    }

    protected function from(array $aql, Build $build)
    {
        if (!isset($aql['from'])) {
            return;
        }

        $build->sql(' FROM');

        if (is_array($aql['from'])) {
            $table = reset($aql['from']);
            $alias = key($aql['from']);
            $build->sql(' ' . $this->quoteIdentifier($table) . " as '" . $alias  . "'");
        }
        else if ($aql['from'][0] === '|') {
            $build->sql(' ' . substr($aql['from'], 1));
        }
        else {
            $build->sql(' ' . $this->quoteIdentifier($aql['from']));
        }
    }

    protected function join(array $aql, Build $build)
    {
        if (!isset($aql['join'])) {
            return;
        }

        if (is_string($aql['join'])) {
            $build->sql(' ' . $aql['join']);
        }
        if (is_array($aql['join'])) {
            $build->sqls($aql['join']);
        }
    }

    protected function group(array $aql, Build $build)
    {
        if (!isset($aql['group'])) {
            return;
        }

        $build->sql(' GROUP BY');

        if (is_string($aql['group'])) {
            $build->sql(' ' . $aql['group']);
        }
        if (is_array($aql['group'])) {
            $build->sqls($aql['group'], ', ');
        }
    }

    protected function having(array $aql, Build $build)
    {
        if (!isset($aql['having'])) {
            return;
        }
        $build->sql(' HAVING ');

        if (is_string($aql['having'])) {
            $build->sql($aql['having']);
        }
        else if (is_array($aql['having'])) {
            $this->condition($aql['having'], $build);
        }
        else {
            throw new Exception();
        }
    }

    protected function order(array $aql, Build $build)
    {
        if (!isset($aql['order'])) {
            return;
        }

        $build->sql(' ORDER BY');

        if (is_string($aql['order'])) {
            $build->sql(' ' . $aql['order']);
        }
        if (is_array($aql['order'])) {
            $build->sqls($aql['order']);
        }
    }

    protected function limit(array $aql, Build $build)
    {
        if (!isset($aql['limit'])) {
            return;
        }

        $build->sql(' LIMIT '. $build->bind((int)$aql['limit']));
    }

    protected function offset(array $aql, Build $build)
    {
        if (!isset($aql['offset'])) {
            return;
        }

        $build->sql(' OFFSET '. $build->bind((int)$aql['offset']));
    }

    protected function where(array $aql, Build $build)
    {
        if (!isset($aql['where'])) {
            return;
        }

        $build->sql(' WHERE ');
        $this->condition($aql['where'], $build);
    }

    protected function set(array $aql, Build $build)
    {
        if (!isset($aql['set'])) {
            return '';
        }

        $build->sql(' SET');

        $first = true;

        foreach ($aql['set'] as $k => $v) {
            $build->sql($first ? ' ' : ', ');
            $first = false;
            if ($k[0] === '|') {
                $build->sql($this->quoteIdentifier(substr($k, 1)) . " = " . $v);
            }
            else  {
                $build->sql($this->quoteIdentifier($k) . " = " . $build->bind($v));
            }
        }
    }

    protected function values(array $aql, Build $build)
    {
        if (!isset($aql['values'])) {
            return '';
        }

        $build->sql(' (');

        $first = true;
        foreach ($aql['values'] as $column => $value) {
            $build->sql($first ? '' : ', ');
            $first = false;
            $column = $column[0] === '|' ? substr($column, 1) : $column;
            $build->sql($this->quoteIdentifier($column));
        }

        $build->sql(') VALUES (');

        $first = true;
        foreach ($aql['values'] as $column => $value) {
            $build->sql($first ? '' : ', ');
            $first = false;
            $build->sql($column[0] === '|' ? $value : $build->bind($value));
        }

        $build->sql(')');
    }

    protected function valuesBulk(array $aql, Build $build)
    {
        if (!isset($aql['values_bulk']) || !is_array($aql['values_bulk']) || count($aql['values_bulk']) <= 0) {
            return '';
        }

        $bulk = array_values($aql['values_bulk']);
        $columns = array_keys($bulk[0]);

        $build->sql(' (');

        $first = true;
        foreach ($columns as $column) {
            $build->sql($first ? '' : ', ');
            $first = false;
            $column = $column[0] === '|' ? substr($column, 1) : $column;
            $build->sql($this->quoteIdentifier($column));
        }

        $build->sql(') VALUES');

        foreach ($bulk as $index => $values) {
            $build->sql($index === 0 ? ' (' : ', (');
            $first = true;
            foreach ($values as $column => $value) {
                $build->sql($first ? '' : ', ');
                $first = false;
                $build->sql($column[0] === '|' ? $value : $build->bind($value));
            }
            $build->sql(')');
        }
    }

    protected function condition(array $condition, Build $build)
    {
        $logical = isset($condition[':operator']) && strtolower($condition[':operator']) == 'or' ? 'OR' : 'AND';
        unset($condition[':operator']);

        $first = true;

        foreach ($condition as $key => $value) {
            $build->sql($first ? '' : ' ' . $logical . ' ');
            $first = false;

            if (is_int($key)) {
                if (is_string($value)) {
                    $build->sql($value);
                }
                else if (is_array($value)) {
                    $build->sql('(');
                    $this->condition($value, $build);
                    $build->sql(')');

                }
                else {
                    throw new Exception();
                }
                continue;
            }

            // key and comparison
            [$key, $comparison] = array_pad(preg_split('/[:\s]/', $key, 2), 2, null);
            $key = $key[0] === '|' ? substr($key, 1) : $this->quoteIdentifier($key);
            $comparison = $comparison !== null ? $comparison : '=';

            // comparison alias
            $comparison = match($comparison) {
                'eq' => '=',
                'gt' => '>',
                'ge' => '>=',
                'lt' => '<',
                'le' => '<=',
                'not' => '!=',
                'neq' => '!=',
                'like' => 'LIKE',
                'in' => 'IN',
                'notin' => 'NOT IN',
                'between' => 'BETWEEN',
                'notbetween' => 'NOT BETWEEN',
                default => $comparison,
            };

            if ($comparison === '=' && is_array($value)) {
                $comparison = 'IN';
            }

            $build->sql("$key $comparison ");
            if ($comparison === 'BETWEEN' || $comparison === 'NOT BETWEEN') {
                $build->sql($build->bind($value[0]) . ' AND ' . $build->bind($value[1]));
            }
            else if ($comparison === 'IN' || $comparison === 'NOT IN') {
                $build->sql('(');
                foreach (array_values($value) as $k => $v) {
                    $build->sql($k === 0 ? '' : ',');
                    $build->sql($build->bind($v));
                }
                $build->sql(')');
            }
            else {
                $build->sql($build->bind($value));
            }
        }
    }

    public function quoteIdentifier(string $identifier): string
    {
        if (!$this->platform instanceof Platform) {
            throw new Exception();
        }

        return $this->platform->quoteIdentifier($identifier);
    }
}
