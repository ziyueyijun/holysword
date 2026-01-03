<?php

/**
 * HolySword Framework - 查询构建器
 * 
 * 提供流式 SQL 构建接口，支持复杂查询、JOIN、分页等。
 * 支持链式调用，自动参数绑定，防止 SQL 注入。
 * 
 * @package    HolySword
 * @subpackage Database
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Database;

use PDO;
use HolySword\Database\Grammar\Grammar;

/**
 * 查询构建器
 * 
 * 提供流式 SQL 构建接口，支持复杂查询。
 * 
 * 使用示例:
 * ```php
 * // 基础查询
 * $users = db()->table('users')->where('status', 1)->get();
 * 
 * // LEFT JOIN
 * $orders = db()->table('orders')
 *     ->select('orders.*', 'users.name as user_name')
 *     ->leftJoin('users', 'orders.user_id', '=', 'users.id')
 *     ->where('orders.status', 'completed')
 *     ->orderBy('orders.created_at', 'desc')
 *     ->limit(10)
 *     ->get();
 * 
 * // 聚合函数
 * $count = db()->table('users')->where('status', 1)->count();
 * $total = db()->table('orders')->sum('amount');
 * ```
 * 
 * @package HolySword\Database
 */
class QueryBuilder
{
    /**
     * 数据库实例
     * 
     * @var DB
     */
    protected DB $db;

    /**
     * SQL 语法生成器
     * 
     * @var Grammar
     */
    protected Grammar $grammar;

    /**
     * 查询的表名
     * 
     * @var string
     */
    protected string $table;

    /**
     * 要查询的字段
     * 
     * @var array
     */
    protected array $columns = ['*'];

    /**
     * JOIN 子句数组
     * 
     * @var array
     */
    protected array $joins = [];

    /**
     * WHERE 条件数组
     * 
     * @var array
     */
    protected array $wheres = [];

    /**
     * OR WHERE 条件数组
     * 
     * @var array
     */
    protected array $orWheres = [];

    /**
     * 绑定参数数组
     * 
     * @var array
     */
    protected array $bindings = [];

    /**
     * ORDER BY 子句数组
     * 
     * @var array
     */
    protected array $orderBy = [];

    /**
     * GROUP BY 子句数组
     * 
     * @var array
     */
    protected array $groupBy = [];

    /**
     * HAVING 子句数组
     * 
     * @var array
     */
    protected array $having = [];

    /**
     * LIMIT 限制数量
     * 
     * @var int|null
     */
    protected ?int $limit = null;

    /**
     * OFFSET 偏移量
     * 
     * @var int|null
     */
    protected ?int $offset = null;

    /**
     * 是否 DISTINCT
     * 
     * @var bool
     */
    protected bool $distinct = false;

    /**
     * 是否使用原生 SELECT 表达式
     * 
     * @var bool
     */
    protected bool $selectRaw = false;

    /**
     * 创建查询构建器实例
     * 
     * @param DB $db 数据库实例
     * @param string $table 表名
     */
    public function __construct(DB $db, string $table)
    {
        $this->db = $db;
        $this->grammar = $db->getGrammar();
        $this->table = $db->getPrefix() . $table;
    }

    /**
     * 设置查询字段
     * 
     * @param string|array ...$columns 字段名
     * @return $this
     * 
     * @example
     * ->select('id', 'name', 'email')
     * ->select(['id', 'name', 'email'])
     * ->select('users.id', 'users.name as user_name')
     */
    public function select(...$columns): self
    {
        if (count($columns) === 1 && is_array($columns[0])) {
            $columns = $columns[0];
        }
        $this->columns = $columns;
        return $this;
    }

    /**
     * 添加更多查询字段
     * 
     * @param string|array ...$columns 字段名
     * @return self
     */
    public function addSelect(...$columns): self
    {
        if (count($columns) === 1 && is_array($columns[0])) {
            $columns = $columns[0];
        }
        if ($this->columns === ['*']) {
            $this->columns = $columns;
        } else {
            $this->columns = array_merge($this->columns, $columns);
        }
        return $this;
    }

    /**
     * 使用原生表达式设置查询字段
     * 
     * 支持聚合函数、数学运算等原生SQL表达式
     * 
     * @param string $expression 原生SQL表达式
     * @param array $bindings 绑定参数
     * @return self
     * 
     * @example
     * ->selectRaw('COUNT(*) as total')
     * ->selectRaw('SUM(amount) as total_amount')
     * ->selectRaw('tool_name, COUNT(*) as usage_count')
     */
    public function selectRaw(string $expression, array $bindings = []): self
    {
        $this->columns = [$expression];
        if (!empty($bindings)) {
            $this->bindings = array_merge($this->bindings, $bindings);
        }
        // 标记为原生选择
        $this->selectRaw = true;
        return $this;
    }

    /**
     * 设置 DISTINCT
     * 
     * 查询结果去重
     * 
     * @return self
     */
    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | JOIN 语句
    |--------------------------------------------------------------------------
    */

    /**
     * INNER JOIN
     * 
     * @example
     * ->join('users', 'orders.user_id', '=', 'users.id')
     */
    public function join(string $table, string $first, string $operator, string $second): self
    {
        $table = $this->db->getPrefix() . $table;
        $this->joins[] = "INNER JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    /**
     * LEFT JOIN
     * 
     * @example
     * ->leftJoin('users', 'orders.user_id', '=', 'users.id')
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $table = $this->db->getPrefix() . $table;
        $this->joins[] = "LEFT JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    /**
     * RIGHT JOIN
     * 
     * @example
     * ->rightJoin('users', 'orders.user_id', '=', 'users.id')
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        $table = $this->db->getPrefix() . $table;
        $this->joins[] = "RIGHT JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    /**
     * CROSS JOIN
     * 
     * @param string $table 要连接的表名
     * @return self
     */
    public function crossJoin(string $table): self
    {
        $table = $this->db->getPrefix() . $table;
        $this->joins[] = "CROSS JOIN {$table}";
        return $this;
    }

    /**
     * 原始 JOIN 语句
     * 
     * @example
     * ->joinRaw('LEFT JOIN users ON users.id = orders.user_id AND users.status = 1')
     */
    public function joinRaw(string $expression): self
    {
        $this->joins[] = $expression;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | WHERE 条件
    |--------------------------------------------------------------------------
    */

    /**
     * WHERE 条件
     * 
     * @param string|array|\Closure $column 字段名或条件数组
     * @param mixed $operator 操作符或值
     * @param mixed $value 值
     * @return $this
     * 
     * @example
     * ->where('status', 1)                      // status = 1
     * ->where('age', '>', 18)                   // age > 18
     * ->where('name', 'like', '%john%')         // name LIKE '%john%'
     * ->where(['status' => 1, 'type' => 'vip']) // 多条件
     */
    public function where($column, $operator = null, $value = null): self
    {
        // 数组形式的多条件
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->where($key, '=', $val);
            }
            return $this;
        }

        // 闭包子查询
        if ($column instanceof \Closure) {
            $this->wheres[] = ['type' => 'nested', 'callback' => $column, 'boolean' => 'AND'];
            return $this;
        }

        // 两参数形式: where('status', 1) => where('status', '=', 1)
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => strtoupper($operator),
            'value' => $value,
            'boolean' => 'AND'
        ];
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * OR WHERE 条件
     * 
     * @param string|array $column 字段名或条件数组
     * @param mixed $operator 操作符或值
     * @param mixed $value 值
     * @return self
     */
    public function orWhere($column, $operator = null, $value = null): self
    {
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->orWhere($key, '=', $val);
            }
            return $this;
        }

        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => strtoupper($operator),
            'value' => $value,
            'boolean' => 'OR'
        ];
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * WHERE IN 条件
     * 
     * @example
     * ->whereIn('id', [1, 2, 3])
     */
    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => "{$column} IN ({$placeholders})",
            'boolean' => 'AND'
        ];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * WHERE NOT IN 条件
     * 
     * @param string $column 字段名
     * @param array $values 值列表
     * @return self
     */
    public function whereNotIn(string $column, array $values): self
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => "{$column} NOT IN ({$placeholders})",
            'boolean' => 'AND'
        ];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * WHERE BETWEEN 条件
     * 
     * @example
     * ->whereBetween('age', [18, 60])
     */
    public function whereBetween(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => "{$column} BETWEEN ? AND ?",
            'boolean' => 'AND'
        ];
        $this->bindings[] = $values[0];
        $this->bindings[] = $values[1];
        return $this;
    }

    /**
     * WHERE NOT BETWEEN 条件
     * 
     * @param string $column 字段名
     * @param array $values [最小值, 最大值]
     * @return self
     */
    public function whereNotBetween(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => "{$column} NOT BETWEEN ? AND ?",
            'boolean' => 'AND'
        ];
        $this->bindings[] = $values[0];
        $this->bindings[] = $values[1];
        return $this;
    }

    /**
     * WHERE IS NULL 条件
     * 
     * @param string $column 字段名
     * @return self
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => "{$column} IS NULL",
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * WHERE IS NOT NULL 条件
     * 
     * @param string $column 字段名
     * @return self
     */
    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => "{$column} IS NOT NULL",
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * WHERE LIKE 条件
     * 
     * @example
     * ->whereLike('name', '%john%')
     */
    public function whereLike(string $column, string $value): self
    {
        return $this->where($column, 'LIKE', $value);
    }

    /**
     * WHERE 日期条件
     * 
     * @example
     * ->whereDate('created_at', '2025-01-01')
     */
    public function whereDate(string $column, string $value): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => "DATE({$column}) = ?",
            'boolean' => 'AND'
        ];
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * WHERE 年份条件
     * 
     * @param string $column 日期字段名
     * @param int $year 年份
     * @return self
     */
    public function whereYear(string $column, int $year): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => "YEAR({$column}) = ?",
            'boolean' => 'AND'
        ];
        $this->bindings[] = $year;
        return $this;
    }

    /**
     * WHERE 月份条件
     * 
     * @param string $column 日期字段名
     * @param int $month 月份 (1-12)
     * @return self
     */
    public function whereMonth(string $column, int $month): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => "MONTH({$column}) = ?",
            'boolean' => 'AND'
        ];
        $this->bindings[] = $month;
        return $this;
    }

    /**
     * WHERE 日期条件
     * 
     * @param string $column 日期字段名
     * @param int $day 天数 (1-31)
     * @return self
     */
    public function whereDay(string $column, int $day): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => "DAY({$column}) = ?",
            'boolean' => 'AND'
        ];
        $this->bindings[] = $day;
        return $this;
    }

    /**
     * 原始 WHERE 条件
     * 
     * @example
     * ->whereRaw('price > IF(type = "vip", 100, 200)')
     */
    public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => 'AND'
        ];
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    /**
     * WHERE EXISTS 子查询
     * 
     * @param \Closure $callback 子查询回调
     * @return self
     */
    public function whereExists(\Closure $callback): self
    {
        $builder = new self($this->db, '');
        $callback($builder);
        $subSql = $builder->toSql();
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => "EXISTS ({$subSql})",
            'boolean' => 'AND'
        ];
        $this->bindings = array_merge($this->bindings, $builder->getBindings());
        return $this;
    }

    /**
     * WHERE 字段比较
     * 
     * @example
     * ->whereColumn('updated_at', '>', 'created_at')
     */
    public function whereColumn(string $first, string $operator, string $second): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => "{$first} {$operator} {$second}",
            'boolean' => 'AND'
        ];
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | ORDER BY / GROUP BY / HAVING
    |--------------------------------------------------------------------------
    */

    /**
     * ORDER BY 排序
     * 
     * @example
     * ->orderBy('created_at', 'desc')
     * ->orderBy('name')
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    /**
     * ORDER BY DESC
     * 
     * @param string $column 字段名
     * @return self
     */
    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * 随机排序
     * 
     * @return self
     */
    public function inRandomOrder(): self
    {
        $this->orderBy[] = 'RAND()';
        return $this;
    }

    /**
     * 原始 ORDER BY
     * 
     * @param string $sql 原始 SQL 表达式
     * @return self
     */
    public function orderByRaw(string $sql): self
    {
        $this->orderBy[] = $sql;
        return $this;
    }

    /**
     * 最新记录（按 created_at 降序）
     * 
     * @param string $column 日期字段名
     * @return self
     */
    public function latest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * 最旧记录（按 created_at 升序）
     * 
     * @param string $column 日期字段名
     * @return self
     */
    public function oldest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'asc');
    }

    /**
     * GROUP BY 分组
     * 
     * @example
     * ->groupBy('category_id')
     * ->groupBy('category_id', 'status')
     */
    public function groupBy(...$columns): self
    {
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    /**
     * HAVING 条件
     * 
     * @example
     * ->having('count', '>', 5)
     */
    public function having(string $column, string $operator, $value): self
    {
        $this->having[] = "{$column} {$operator} ?";
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * 原始 HAVING
     * 
     * @param string $sql 原始 SQL 表达式
     * @param array $bindings 绑定参数
     * @return self
     */
    public function havingRaw(string $sql, array $bindings = []): self
    {
        $this->having[] = $sql;
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | LIMIT / OFFSET
    |--------------------------------------------------------------------------
    */

    /**
     * LIMIT 限制数量
     * 
     * @param int $limit 限制数量
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * take 的别名
     * 
     * @param int $limit 限制数量
     * @return self
     */
    public function take(int $limit): self
    {
        return $this->limit($limit);
    }

    /**
     * OFFSET 偏移量
     * 
     * @param int $offset 偏移量
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * skip 的别名
     * 
     * @param int $offset 偏移量
     * @return self
     */
    public function skip(int $offset): self
    {
        return $this->offset($offset);
    }

    /**
     * 分页
     * 
     * @example
     * ->forPage(2, 15)  // 第 2 页，每页 15 条
     */
    public function forPage(int $page, int $perPage = 15): self
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | 聚合函数
    |--------------------------------------------------------------------------
    */

    /**
     * COUNT 统计
     * 
     * @param string $column 字段名
     * @return int 记录数
     */
    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('COUNT', $column);
    }

    /**
     * SUM 求和
     * 
     * @param string $column 字段名
     * @return float 总和
     */
    public function sum(string $column): float
    {
        return (float) $this->aggregate('SUM', $column);
    }

    /**
     * AVG 平均值
     * 
     * @param string $column 字段名
     * @return float 平均值
     */
    public function avg(string $column): float
    {
        return (float) $this->aggregate('AVG', $column);
    }

    /**
     * MAX 最大值
     * 
     * @param string $column 字段名
     * @return mixed 最大值
     */
    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    /**
     * MIN 最小值
     * 
     * @param string $column 字段名
     * @return mixed 最小值
     */
    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    /**
     * 执行聚合函数
     * 
     * @param string $function 聚合函数名称
     * @param string $column 字段名
     * @return mixed 聚合结果
     */
    protected function aggregate(string $function, string $column): mixed
    {
        $this->columns = ["{$function}({$column}) as aggregate"];
        $result = $this->first();
        return $result['aggregate'] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | 执行查询
    |--------------------------------------------------------------------------
    */

    /**
     * 获取所有结果
     * 
     * @return array 查询结果数组
     */
    public function get(): array
    {
        $sql = $this->toSql();
        return $this->db->query($sql, $this->bindings);
    }

    /**
     * 获取第一条结果
     * 
     * @return array|null 第一条记录或 null
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * 根据 ID 查找
     * 
     * @param int $id 主键 ID
     * @param string $column 主键字段名
     * @return array|null 记录或 null
     */
    public function find(int $id, string $column = 'id'): ?array
    {
        return $this->where($column, $id)->first();
    }

    /**
     * 获取单个字段值
     * 
     * @example
     * $name = db()->table('users')->where('id', 1)->value('name');
     */
    public function value(string $column): mixed
    {
        $result = $this->select($column)->first();
        return $result[$column] ?? null;
    }

    /**
     * 获取某列的所有值
     * 
     * @example
     * $names = db()->table('users')->pluck('name');
     * $names = db()->table('users')->pluck('name', 'id'); // [id => name]
     */
    public function pluck(string $column, ?string $key = null): array
    {
        $columns = $key ? [$key, $column] : [$column];
        $this->columns = $columns;
        $results = $this->get();

        if ($key) {
            $plucked = [];
            foreach ($results as $row) {
                $plucked[$row[$key]] = $row[$column];
            }
            return $plucked;
        }

        return array_column($results, $column);
    }

    /**
     * 检查是否存在
     * 
     * @return bool 是否存在记录
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * 检查是否不存在
     * 
     * @return bool 是否不存在记录
     */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | 插入 / 更新 / 删除
    |--------------------------------------------------------------------------
    */

    /**
     * 插入数据
     * 
     * @param array $data 要插入的数据
     * @return int 插入记录的 ID
     */
    public function insert(array $data): int
    {
        return $this->db->insert(str_replace($this->db->getPrefix(), '', $this->table), $data);
    }

    /**
     * 批量插入
     * 
     * @param array $rows 要插入的数据数组
     * @return int 影响的行数
     */
    public function insertBatch(array $rows): int
    {
        return $this->db->insertBatch(str_replace($this->db->getPrefix(), '', $this->table), $rows);
    }

    /**
     * 插入并获取 ID
     * 
     * @param array $data 要插入的数据
     * @return int 插入记录的 ID
     */
    public function insertGetId(array $data): int
    {
        return $this->insert($data);
    }

    /**
     * 更新数据
     * 
     * @param array $data 要更新的数据
     * @return int 影响的行数
     */
    public function update(array $data): int
    {
        $setParts = [];
        $bindings = [];
        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = ?";
            $bindings[] = $value;
        }
        $setStr = implode(', ', $setParts);

        $sql = "UPDATE {$this->table} SET {$setStr}";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }

        $bindings = array_merge($bindings, $this->bindings);
        return $this->db->execute($sql, $bindings);
    }

    /**
     * 自增
     * 
     * @param string $column 字段名
     * @param int $amount 增加数量
     * @param array $extra 额外更新的字段
     * @return int 影响的行数
     */
    public function increment(string $column, int $amount = 1, array $extra = []): int
    {
        return $this->updateRaw($column, "{$column} + ?", [$amount], $extra);
    }

    /**
     * 自减
     * 
     * @param string $column 字段名
     * @param int $amount 减少数量
     * @param array $extra 额外更新的字段
     * @return int 影响的行数
     */
    public function decrement(string $column, int $amount = 1, array $extra = []): int
    {
        return $this->updateRaw($column, "{$column} - ?", [$amount], $extra);
    }

    /**
     * 原始更新
     * 
     * @param string $column 字段名
     * @param string $expression SQL 表达式
     * @param array $expressionBindings 表达式绑定参数
     * @param array $extra 额外更新的字段
     * @return int 影响的行数
     */
    protected function updateRaw(string $column, string $expression, array $expressionBindings, array $extra = []): int
    {
        $setParts = ["{$column} = {$expression}"];
        $bindings = $expressionBindings;

        foreach ($extra as $col => $value) {
            $setParts[] = "{$col} = ?";
            $bindings[] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts);
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }

        $bindings = array_merge($bindings, $this->bindings);
        return $this->db->execute($sql, $bindings);
    }

    /**
     * 删除数据
     * 
     * @return int 影响的行数
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }

        return $this->db->execute($sql, $this->bindings);
    }

    /**
     * 清空表
     * 
     * @return void
     */
    public function truncate(): void
    {
        $this->db->execute("TRUNCATE TABLE {$this->table}");
    }

    /*
    |--------------------------------------------------------------------------
    | SQL 构建
    |--------------------------------------------------------------------------
    */

    /**
     * 生成 SQL 语句
     * 
     * @return string SQL 查询语句
     */
    public function toSql(): string
    {
        $sql = 'SELECT ';

        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }

        $sql .= implode(', ', $this->columns);
        $sql .= " FROM {$this->table}";

        // JOINs
        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        // WHERE
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }

        // GROUP BY
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        // HAVING
        if (!empty($this->having)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->having);
        }

        // ORDER BY
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        // LIMIT/OFFSET - 使用 Grammar 处理不同数据库的语法差异
        if ($this->limit !== null || $this->offset !== null) {
            // SQL Server 需要 ORDER BY 才能使用 OFFSET FETCH
            if ($this->db->getDriverName() === 'sqlsrv' && empty($this->orderBy)) {
                $sql .= ' ORDER BY (SELECT NULL)';
            }
            $sql .= ' ' . $this->grammar->compileLimit($this->limit, $this->offset);
        }

        return $sql;
    }

    /**
     * 编译 WHERE 条件
     * 
     * @return string WHERE 子句
     */
    protected function compileWheres(): string
    {
        $parts = [];

        foreach ($this->wheres as $index => $where) {
            $sql = '';

            if ($where['type'] === 'basic') {
                $sql = "{$where['column']} {$where['operator']} ?";
            } elseif ($where['type'] === 'raw') {
                $sql = $where['sql'];
            } elseif ($where['type'] === 'nested') {
                $builder = new self($this->db, '');
                $where['callback']($builder);
                $sql = '(' . $builder->compileWheres() . ')';
                // 合并嵌套查询的绑定
                array_splice($this->bindings, count($this->bindings), 0, $builder->getBindings());
            }

            if ($index === 0) {
                $parts[] = $sql;
            } else {
                $parts[] = $where['boolean'] . ' ' . $sql;
            }
        }

        return implode(' ', $parts);
    }

    /**
     * 获取绑定参数
     * 
     * @return array 绑定参数数组
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * 调试输出 SQL
     * 
     * 输出 SQL 并终止程序
     * 
     * @return void
     */
    public function dd(): void
    {
        $sql = $this->toSql();
        $bindings = $this->bindings;
        
        // 替换 ? 为实际值（仅用于调试）
        foreach ($bindings as $binding) {
            $value = is_string($binding) ? "'{$binding}'" : $binding;
            $sql = preg_replace('/\?/', (string) $value, $sql, 1);
        }

        if (function_exists('dd')) {
            dd($sql);
        } else {
            var_dump($sql);
            die();
        }
    }

    /**
     * 打印 SQL（不终止）
     * 
     * @return self
     */
    public function dump(): self
    {
        $sql = $this->toSql();
        if (function_exists('dump')) {
            dump($sql, $this->bindings);
        } else {
            var_dump($sql, $this->bindings);
        }
        return $this;
    }

    /**
     * 原始表达式（用于特殊 SQL）
     * 
     * @param string $expression SQL 表达式
     * @return string 返回相同的表达式
     */
    public function raw(string $expression): string
    {
        return $expression;
    }
}
