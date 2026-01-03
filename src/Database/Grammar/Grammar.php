<?php

/**
 * HolySword Framework - SQL 语法基类
 * 
 * 提供 SQL 语法生成的抽象基类，支持多种数据库。
 * 子类需要实现特定数据库的语法差异。
 * 
 * @package    HolySword
 * @subpackage Database\Grammar
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Database\Grammar;

/**
 * SQL 语法基类
 * 
 * 负责生成跨数据库兼容的 SQL 语句。
 * 每种数据库有自己的子类实现特定语法。
 * 
 * @package HolySword\Database\Grammar
 */
abstract class Grammar
{
    /**
     * 表前缀
     * 
     * @var string
     */
    protected string $tablePrefix = '';

    /**
     * 操作符映射表
     * 
     * @var array
     */
    protected array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'ilike',
        'between', 'not between',
        'in', 'not in',
        'is null', 'is not null',
        'regexp', 'not regexp',
    ];

    /**
     * 设置表前缀
     * 
     * @param string $prefix 表前缀
     * @return static
     */
    public function setTablePrefix(string $prefix): static
    {
        $this->tablePrefix = $prefix;
        return $this;
    }

    /**
     * 获取表前缀
     * 
     * @return string
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * 包装表名（添加前缀和引用符）
     * 
     * @param string $table 表名
     * @return string
     */
    public function wrapTable(string $table): string
    {
        // 如果已有别名，分开处理
        if (stripos($table, ' as ') !== false) {
            [$table, $alias] = preg_split('/\s+as\s+/i', $table);
            return $this->wrapTable(trim($table)) . ' AS ' . $this->wrapValue(trim($alias));
        }

        return $this->wrapValue($this->tablePrefix . $table);
    }

    /**
     * 包装列名（处理别名和表.列格式）
     * 
     * @param string $column 列名
     * @return string
     */
    public function wrapColumn(string $column): string
    {
        // 处理 * 通配符
        if ($column === '*') {
            return '*';
        }

        // 处理原始表达式
        if ($this->isRawExpression($column)) {
            return $column;
        }

        // 处理别名: column as alias
        if (stripos($column, ' as ') !== false) {
            [$column, $alias] = preg_split('/\s+as\s+/i', $column);
            return $this->wrapColumn(trim($column)) . ' AS ' . $this->wrapValue(trim($alias));
        }

        // 处理 table.column 格式
        if (strpos($column, '.') !== false) {
            $parts = explode('.', $column);
            $wrapped = [];
            foreach ($parts as $i => $part) {
                // 最后一个是列名，其他是表名
                if ($i === count($parts) - 1) {
                    $wrapped[] = $part === '*' ? '*' : $this->wrapValue($part);
                } else {
                    $wrapped[] = $this->wrapValue($part);
                }
            }
            return implode('.', $wrapped);
        }

        return $this->wrapValue($column);
    }

    /**
     * 包装值（添加数据库特定的引用符）
     * 
     * 每个数据库子类需要重写此方法
     * 
     * @param string $value 值
     * @return string
     */
    abstract public function wrapValue(string $value): string;

    /**
     * 检查是否为原始表达式
     * 
     * @param string $expression 表达式
     * @return bool
     */
    protected function isRawExpression(string $expression): bool
    {
        // 检查是否包含函数调用或特殊语法
        return preg_match('/[()]+|^\s*(COUNT|SUM|AVG|MAX|MIN|CONCAT|COALESCE|IF|CASE)\s*\(/i', $expression) === 1;
    }

    /**
     * 编译 SELECT 子句
     * 
     * @param array $columns 列名数组
     * @param bool $distinct 是否去重
     * @return string
     */
    public function compileSelect(array $columns, bool $distinct = false, bool $raw = false): string
    {
        $sql = 'SELECT ';
        
        if ($distinct) {
            $sql .= 'DISTINCT ';
        }

        // 原生表达式不进行包装
        if ($raw) {
            return $sql . implode(', ', $columns);
        }

        $wrappedColumns = array_map(fn($col) => $this->wrapColumn($col), $columns);
        
        return $sql . implode(', ', $wrappedColumns);
    }

    /**
     * 编译 FROM 子句
     * 
     * @param string $table 表名
     * @return string
     */
    public function compileFrom(string $table): string
    {
        return 'FROM ' . $this->wrapTable($table);
    }

    /**
     * 编译 JOIN 子句
     * 
     * @param array $joins JOIN 配置数组
     * @return string
     */
    public function compileJoins(array $joins): string
    {
        $compiled = [];

        foreach ($joins as $join) {
            $type = strtoupper($join['type'] ?? 'INNER');
            $table = $this->wrapTable($join['table']);
            $first = $this->wrapColumn($join['first']);
            $operator = $join['operator'];
            $second = $this->wrapColumn($join['second']);

            $compiled[] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
        }

        return implode(' ', $compiled);
    }

    /**
     * 编译 WHERE 子句
     * 
     * @param array $wheres WHERE 条件数组
     * @return string
     */
    public function compileWheres(array $wheres): string
    {
        if (empty($wheres)) {
            return '';
        }

        $parts = [];

        foreach ($wheres as $index => $where) {
            $sql = $this->compileWhere($where);

            if ($index === 0) {
                $parts[] = $sql;
            } else {
                $parts[] = $where['boolean'] . ' ' . $sql;
            }
        }

        return 'WHERE ' . implode(' ', $parts);
    }

    /**
     * 编译单个 WHERE 条件
     * 
     * @param array $where WHERE 条件
     * @return string
     */
    protected function compileWhere(array $where): string
    {
        $type = $where['type'] ?? 'basic';

        return match ($type) {
            'basic' => $this->compileWhereBasic($where),
            'in' => $this->compileWhereIn($where),
            'not_in' => $this->compileWhereNotIn($where),
            'null' => $this->compileWhereNull($where),
            'not_null' => $this->compileWhereNotNull($where),
            'between' => $this->compileWhereBetween($where),
            'raw' => $where['sql'],
            'nested' => '(' . $this->compileWheres($where['wheres']) . ')',
            'exists' => 'EXISTS (' . $where['query'] . ')',
            'column' => $this->compileWhereColumn($where),
            default => $where['sql'] ?? '',
        };
    }

    /**
     * 编译基本 WHERE 条件
     * 
     * @param array $where WHERE 条件
     * @return string
     */
    protected function compileWhereBasic(array $where): string
    {
        $column = $this->wrapColumn($where['column']);
        $operator = strtoupper($where['operator']);
        
        return "{$column} {$operator} ?";
    }

    /**
     * 编译 WHERE IN 条件
     * 
     * @param array $where WHERE 条件
     * @return string
     */
    protected function compileWhereIn(array $where): string
    {
        $column = $this->wrapColumn($where['column']);
        $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
        
        return "{$column} IN ({$placeholders})";
    }

    /**
     * 编译 WHERE NOT IN 条件
     * 
     * @param array $where WHERE 条件
     * @return string
     */
    protected function compileWhereNotIn(array $where): string
    {
        $column = $this->wrapColumn($where['column']);
        $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
        
        return "{$column} NOT IN ({$placeholders})";
    }

    /**
     * 编译 WHERE IS NULL 条件
     * 
     * @param array $where WHERE 条件
     * @return string
     */
    protected function compileWhereNull(array $where): string
    {
        return $this->wrapColumn($where['column']) . ' IS NULL';
    }

    /**
     * 编译 WHERE IS NOT NULL 条件
     * 
     * @param array $where WHERE 条件
     * @return string
     */
    protected function compileWhereNotNull(array $where): string
    {
        return $this->wrapColumn($where['column']) . ' IS NOT NULL';
    }

    /**
     * 编译 WHERE BETWEEN 条件
     * 
     * @param array $where WHERE 条件
     * @return string
     */
    protected function compileWhereBetween(array $where): string
    {
        $column = $this->wrapColumn($where['column']);
        $not = $where['not'] ?? false;
        
        return $column . ($not ? ' NOT BETWEEN' : ' BETWEEN') . ' ? AND ?';
    }

    /**
     * 编译 WHERE 列比较条件
     * 
     * @param array $where WHERE 条件
     * @return string
     */
    protected function compileWhereColumn(array $where): string
    {
        $first = $this->wrapColumn($where['first']);
        $operator = $where['operator'];
        $second = $this->wrapColumn($where['second']);
        
        return "{$first} {$operator} {$second}";
    }

    /**
     * 编译 GROUP BY 子句
     * 
     * @param array $groups 分组列
     * @return string
     */
    public function compileGroupBy(array $groups): string
    {
        if (empty($groups)) {
            return '';
        }

        $wrapped = array_map(fn($col) => $this->wrapColumn($col), $groups);
        
        return 'GROUP BY ' . implode(', ', $wrapped);
    }

    /**
     * 编译 HAVING 子句
     * 
     * @param array $havings HAVING 条件
     * @return string
     */
    public function compileHaving(array $havings): string
    {
        if (empty($havings)) {
            return '';
        }

        $parts = [];

        foreach ($havings as $index => $having) {
            $sql = $this->wrapColumn($having['column']) . ' ' . $having['operator'] . ' ?';
            
            if ($index === 0) {
                $parts[] = $sql;
            } else {
                $parts[] = ($having['boolean'] ?? 'AND') . ' ' . $sql;
            }
        }

        return 'HAVING ' . implode(' ', $parts);
    }

    /**
     * 编译 ORDER BY 子句
     * 
     * @param array $orders 排序配置
     * @return string
     */
    public function compileOrderBy(array $orders): string
    {
        if (empty($orders)) {
            return '';
        }

        $compiled = [];

        foreach ($orders as $order) {
            if (isset($order['raw'])) {
                $compiled[] = $order['raw'];
            } else {
                $column = $this->wrapColumn($order['column']);
                $direction = strtoupper($order['direction'] ?? 'ASC');
                $compiled[] = "{$column} {$direction}";
            }
        }

        return 'ORDER BY ' . implode(', ', $compiled);
    }

    /**
     * 编译 LIMIT 和 OFFSET 子句
     * 
     * 不同数据库可能有不同语法，子类可重写
     * 
     * @param int|null $limit 限制数量
     * @param int|null $offset 偏移量
     * @return string
     */
    public function compileLimit(?int $limit, ?int $offset = null): string
    {
        $sql = '';

        if ($limit !== null) {
            $sql .= "LIMIT {$limit}";
        }

        if ($offset !== null && $offset > 0) {
            $sql .= " OFFSET {$offset}";
        }

        return $sql;
    }

    /**
     * 编译 INSERT 语句
     * 
     * @param string $table 表名
     * @param array $columns 列名
     * @return string
     */
    public function compileInsert(string $table, array $columns): string
    {
        $table = $this->wrapTable($table);
        $wrappedColumns = array_map(fn($col) => $this->wrapValue($col), $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        return "INSERT INTO {$table} (" . implode(', ', $wrappedColumns) . ") VALUES ({$placeholders})";
    }

    /**
     * 编译批量 INSERT 语句
     * 
     * @param string $table 表名
     * @param array $columns 列名
     * @param int $rowCount 行数
     * @return string
     */
    public function compileInsertBatch(string $table, array $columns, int $rowCount): string
    {
        $table = $this->wrapTable($table);
        $wrappedColumns = array_map(fn($col) => $this->wrapValue($col), $columns);
        $singleRow = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $rows = implode(', ', array_fill(0, $rowCount, $singleRow));

        return "INSERT INTO {$table} (" . implode(', ', $wrappedColumns) . ") VALUES {$rows}";
    }

    /**
     * 编译 UPDATE 语句
     * 
     * @param string $table 表名
     * @param array $columns 列名
     * @return string
     */
    public function compileUpdate(string $table, array $columns): string
    {
        $table = $this->wrapTable($table);
        $setParts = [];

        foreach ($columns as $column) {
            $setParts[] = $this->wrapValue($column) . ' = ?';
        }

        return "UPDATE {$table} SET " . implode(', ', $setParts);
    }

    /**
     * 编译 DELETE 语句
     * 
     * @param string $table 表名
     * @return string
     */
    public function compileDelete(string $table): string
    {
        return "DELETE FROM " . $this->wrapTable($table);
    }

    /**
     * 编译 TRUNCATE 语句
     * 
     * @param string $table 表名
     * @return string
     */
    public function compileTruncate(string $table): string
    {
        return "TRUNCATE TABLE " . $this->wrapTable($table);
    }

    /**
     * 编译聚合函数
     * 
     * @param string $function 函数名
     * @param string $column 列名
     * @return string
     */
    public function compileAggregate(string $function, string $column): string
    {
        $function = strtoupper($function);
        $column = $column === '*' ? '*' : $this->wrapColumn($column);
        
        return "{$function}({$column}) AS aggregate";
    }

    /**
     * 获取当前时间表达式
     * 
     * 不同数据库可能有差异，子类可重写
     * 
     * @return string
     */
    public function currentTimestamp(): string
    {
        return 'CURRENT_TIMESTAMP';
    }

    /**
     * 获取日期格式化函数
     * 
     * @param string $column 列名
     * @param string $format 格式
     * @return string
     */
    abstract public function dateFormat(string $column, string $format): string;

    /**
     * 获取日期函数（提取日期部分）
     * 
     * @param string $column 列名
     * @return string
     */
    abstract public function date(string $column): string;

    /**
     * 获取自增ID
     * 
     * 不同数据库获取方式不同
     * 
     * @return string|null 返回 SQL 或 null（使用 PDO lastInsertId）
     */
    public function getLastInsertIdQuery(): ?string
    {
        return null; // 默认使用 PDO lastInsertId
    }
}
