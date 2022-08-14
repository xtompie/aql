<?php

declare(strict_types=1);

namespace Xtompie\Aql;

use Exception;

class AqlService
{
    public function __construct(
        protected array $keywords = [],
        protected string $quote = '`',
    ) {
        if (!$keywords) {
            $this->initializeDefaultKeywords();
        }
    }

    public function __invoke(array $aql): AqlResult
    {
        $build = new AqlBuild();

        $this->select($aql, $build);
        $this->insert($aql, $build);
        $this->update($aql, $build);
        $this->delete($aql, $build);
        $this->from($aql, $build);
        $this->set($aql, $build);
        $this->join($aql, $build);
        $this->where($aql, $build);
        $this->group($aql, $build);
        $this->having($aql, $build);
        $this->order($aql, $build);
        $this->limit($aql, $build);
        $this->offset($aql, $build);

        return $build->result();
    }

    protected function select(array $aql, AqlBuild $build)
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
                $field = $v[0] === '|' ? substr($v, 1) : $this->quote($v);
                if (!is_int($k)) {
                    $field .= " as " . ($k[0] === '|' ? substr((string)$k, 1) : "'$k'");
                }
                $fields[] = $field;
            }
            $build->sqls($fields, ', ');
        }
    }

    protected function insert(array $aql, AqlBuild $build)
    {
        if (!isset($aql['insert'])) {
            return;
        }
        $build->sql(" INSERT INTO " . $this->quote($aql['insert']));
    }

    protected function update(array $aql, AqlBuild $build)
    {
        if (!isset($aql['update'])) {
            return;
        }
        $build->sql(" UPDATE " . $this->quote($aql['update']));
    }

    protected function delete(array $aql, AqlBuild $build)
    {
        if (!isset($aql['delete'])) {
            return;
        }
        $build->sql(" DELETE FROM " . $this->quote($aql['delete']));
    }

    protected function from(array $aql, AqlBuild $build)
    {
        if (!isset($aql['from'])) {
            return;
        }

        $build->sql(' FROM');

        if (is_array($aql['from'])) {
            $table = reset($aql['from']);
            $alias = key($aql['from']);
            $build->sql(' ' . $this->quote($table) . " as '" . $alias  . "'");
        }
        else if ($aql['from'][0] === '|') {
            $build->sql(' ' . substr($aql['from'], 1));
        }
        else {
            $build->sql(' ' . $this->quote($aql['from']));
        }
    }

    protected function join(array $aql, AqlBuild $build)
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

    protected function group(array $aql, AqlBuild $build)
    {
        if (!isset($aql['group'])) {
            return;
        }

        $build->sql(' GROUP BY');

        if (is_string($aql['group'])) {
            $build->sql(' ' . $aql['group']);
        }
        if (is_array($aql['group'])) {
            $build->sqls($aql['group']);
        }
    }

    protected function having(array $aql, AqlBuild $build)
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

    protected function order(array $aql, AqlBuild $build)
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

    protected function limit(array $aql, AqlBuild $build)
    {
        if (!isset($aql['limit'])) {
            return;
        }

        $build->sql(' LIMIT '. $build->bind((int)$aql['limit']));
    }

    protected function offset(array $aql, AqlBuild $build)
    {
        if (!isset($aql['offset'])) {
            return;
        }

        $build->sql(' OFFSET '. $build->bind((int)$aql['offset']));
    }

    protected function where(array $aql, AqlBuild $build)
    {
        if (!isset($aql['where'])) {
            return;
        }

        $build->sql(' WHERE ');
        $this->condition($aql['where'], $build);
    }

    protected function set(array $aql, AqlBuild $build)
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
                $build->sql($this->quote(substr($k, 1)) . " = " . $v);
            }
            else  {
                $build->sql($this->quote($k) . " = " . $build->bind($v));
            }
        }
    }

    protected function quote(string $identifier): string
    {
        if (strpos($identifier, '.') !== false) {
            $parts = explode('.', $identifier);
            $parts = array_map(fn ($part) => $this->quoteSingle($part), $parts);
            return implode('.', $parts);
        }

        return $this->quoteSingle($identifier);
    }

    protected function quoteSingle(string $identifier): string
    {
        if (!isset($this->keywords[strtoupper($identifier)])) {
            return $identifier;
        }

        return $this->quote . str_replace($this->quote, $this->quote . $this->quote, $identifier) . $this->quote;
    }

    protected function condition(array $condition, AqlBuild $build)
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
            [$key, $comparison] = array_pad(preg_split('/[:\-\s]/', $key, 2), 2, null);
            $key = $key[0] === '|' ? substr($key, 1) : $this->quote($key);
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

    protected function initializeDefaultKeywords()
    {
        $this->keywords = array_flip([
            'ACCESSIBLE',
            'ADD',
            'ALL',
            'ALTER',
            'ANALYZE',
            'AND',
            'AS',
            'ASC',
            'ASENSITIVE',
            'BEFORE',
            'BETWEEN',
            'BIGINT',
            'BINARY',
            'BLOB',
            'BOTH',
            'BY',
            'CALL',
            'CASCADE',
            'CASE',
            'CHANGE',
            'CHAR',
            'CHARACTER',
            'CHECK',
            'COLLATE',
            'COLUMN',
            'CONDITION',
            'CONNECTION',
            'CONSTRAINT',
            'CONTINUE',
            'CONVERT',
            'CREATE',
            'CROSS',
            'CURRENT_DATE',
            'CURRENT_TIME',
            'CURRENT_TIMESTAMP',
            'CURRENT_USER',
            'CURSOR',
            'DATABASE',
            'DATABASES',
            'DAY_HOUR',
            'DAY_MICROSECOND',
            'DAY_MINUTE',
            'DAY_SECOND',
            'DEC',
            'DECIMAL',
            'DECLARE',
            'DEFAULT',
            'DELAYED',
            'DELETE',
            'DESC',
            'DESCRIBE',
            'DETERMINISTIC',
            'DISTINCT',
            'DISTINCTROW',
            'DIV',
            'DOUBLE',
            'DROP',
            'DUAL',
            'EACH',
            'ELSE',
            'ELSEIF',
            'ENCLOSED',
            'ESCAPED',
            'EXISTS',
            'EXIT',
            'EXPLAIN',
            'FALSE',
            'FETCH',
            'FLOAT',
            'FLOAT4',
            'FLOAT8',
            'FOR',
            'FORCE',
            'FOREIGN',
            'FROM',
            'FULLTEXT',
            'GENERAL',
            'GOTO',
            'GRANT',
            'GROUP',
            'HAVING',
            'HIGH_PRIORITY',
            'HOUR_MICROSECOND',
            'HOUR_MINUTE',
            'HOUR_SECOND',
            'IF',
            'IGNORE',
            'IGNORE_SERVER_IDS',
            'IN',
            'INDEX',
            'INFILE',
            'INNER',
            'INOUT',
            'INSENSITIVE',
            'INSERT',
            'INT',
            'INT1',
            'INT2',
            'INT3',
            'INT4',
            'INT8',
            'INTEGER',
            'INTERVAL',
            'INTO',
            'IS',
            'ITERATE',
            'JOIN',
            'KEY',
            'KEYS',
            'KILL',
            'LABEL',
            'LEADING',
            'LEAVE',
            'LEFT',
            'LIKE',
            'LIMIT',
            'LINEAR',
            'LINES',
            'LOAD',
            'LOCALTIME',
            'LOCALTIMESTAMP',
            'LOCK',
            'LONG',
            'LONGBLOB',
            'LONGTEXT',
            'LOOP',
            'LOW_PRIORITY',
            'MASTER_HEARTBEAT_PERIOD',
            'MASTER_SSL_VERIFY_SERVER_CERT',
            'MATCH',
            'MAXVALUE',
            'MEDIUMBLOB',
            'MEDIUMINT',
            'MEDIUMTEXT',
            'MIDDLEINT',
            'MINUTE_MICROSECOND',
            'MINUTE_SECOND',
            'MOD',
            'MODIFIES',
            'NATURAL',
            'NO_WRITE_TO_BINLOG',
            'NOT',
            'NULL',
            'NUMERIC',
            'ON',
            'OPTIMIZE',
            'OPTION',
            'OPTIONALLY',
            'OR',
            'ORDER',
            'OUT',
            'OUTER',
            'OUTFILE',
            'PARTITION',
            'PRECISION',
            'PRIMARY',
            'PROCEDURE',
            'PURGE',
            'RAID0',
            'RANGE',
            'READ',
            'READ_WRITE',
            'READS',
            'REAL',
            'RECURSIVE',
            'REFERENCES',
            'REGEXP',
            'RELEASE',
            'RENAME',
            'REPEAT',
            'REPLACE',
            'REQUIRE',
            'RESIGNAL',
            'RESTRICT',
            'RETURN',
            'REVOKE',
            'RIGHT',
            'RLIKE',
            'ROWS',
            'SCHEMA',
            'SCHEMAS',
            'SECOND_MICROSECOND',
            'SELECT',
            'SENSITIVE',
            'SEPARATOR',
            'SET',
            'SHOW',
            'SIGNAL',
            'SLOW',
            'SMALLINT',
            'SONAME',
            'SPATIAL',
            'SPECIFIC',
            'SQL',
            'SQL_BIG_RESULT',
            'SQL_CALC_FOUND_ROWS',
            'SQL_SMALL_RESULT',
            'SQLEXCEPTION',
            'SQLSTATE',
            'SQLWARNING',
            'SSL',
            'STARTING',
            'STRAIGHT_JOIN',
            'TABLE',
            'TERMINATED',
            'THEN',
            'TINYBLOB',
            'TINYINT',
            'TINYTEXT',
            'TO',
            'TRAILING',
            'TRIGGER',
            'TRUE',
            'UNDO',
            'UNION',
            'UNIQUE',
            'UNLOCK',
            'UNSIGNED',
            'UPDATE',
            'USAGE',
            'USE',
            'USING',
            'UTC_DATE',
            'UTC_TIME',
            'UTC_TIMESTAMP',
            'VALUES',
            'VARBINARY',
            'VARCHAR',
            'VARCHARACTER',
            'VARYING',
            'WHEN',
            'WHERE',
            'WHILE',
            'WITH',
            'WRITE',
            'X509',
            'XOR',
            'YEAR_MONTH',
            'ZEROFILL',
        ]);
    }
}
