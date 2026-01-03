<?php

/**
 * HolySword Framework - 模型查询构建器
 * 
 * 提供模型级别的查询构建功能，返回模型实例而非数组。
 * 
 * @package    HolySword
 * @subpackage Database\Model
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Database\Model;

use Closure;

/**
 * 模型查询构建器
 * 
 * 继承自 QueryBuilder 但返回模型实例
 * 
 * @package HolySword\Database\Model
 */
class Builder
{
    /**
     * 模型实例
     * 
     * @var Model
     */
    protected Model $model;

    /**
     * 查询的列
     * 
     * @var array
     */
    protected array $columns = ['*'];

    /**
     * 是否使用原生 SELECT 表达式
     * 
     * @var bool
     */
    protected bool $selectRaw = false;

    /**
     * JOIN 子句
     * 
     * @var array
     */
    protected array $joins = [];

    /**
     * WHERE 条件
     * 
     * @var array
     */
    protected array $wheres = [];

    /**
     * 绑定参数
     * 
     * @var array
     */
    protected array $bindings = [];

    /**
     * ORDER BY 子句
     * 
     * @var array
     */
    protected array $orderBy = [];

    /**
     * GROUP BY 子句
     * 
     * @var array
     */
    protected array $groupBy = [];

    /**
     * HAVING 子句
     * 
     * @var array
     */
    protected array $having = [];

    /**
     * LIMIT
     * 
     * @var int|null
     */
    protected ?int $limit = null;

    /**
     * OFFSET
     * 
     * @var int|null
     */
    protected ?int $offset = null;

    /**
     * DISTINCT
     * 
     * @var bool
     */
    protected bool $distinct = false;

    /**
     * 预加载的关联
     * 
     * @var array
     */
    protected array $eagerLoad = [];

    /**
     * 移除的全局作用域
     * 
     * @var array
     */
    protected array $removedScopes = [];

    /**
     * 创建构建器实例
     * 
     * @param Model $model 模型实例
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * 应用全局作用域
     * 
     * @return static
     */
    public function withGlobalScopes(): static
    {
        foreach ($this->model->getGlobalScopes() as $identifier => $scope) {
            if (!in_array($identifier, $this->removedScopes)) {
                if ($scope instanceof Closure) {
                    $scope($this);
                } elseif (method_exists($scope, 'apply')) {
                    $scope->apply($this, $this->model);
                }
            }
        }

        return $this;
    }

    /**
     * 移除全局作用域
     * 
     * @param string|array $scopes 作用域标识符
     * @return static
     */
    public function withoutGlobalScope(string|array $scopes): static
    {
        $this->removedScopes = array_merge(
            $this->removedScopes,
            is_array($scopes) ? $scopes : [$scopes]
        );

        return $this;
    }

    /**
     * 移除所有全局作用域
     * 
     * @return static
     */
    public function withoutGlobalScopes(): static
    {
        $this->removedScopes = array_keys($this->model->getGlobalScopes());
        return $this;
    }

    // ==================== 查询构建方法 ====================

    /**
     * 设置查询列
     * 
     * @param array|string ...$columns 列名
     * @return static
     */
    public function select(array|string ...$columns): static
    {
        $this->columns = is_array($columns[0] ?? null) ? $columns[0] : $columns;
        return $this;
    }

    /**
     * 添加查询列
     * 
     * @param array|string ...$columns 列名
     * @return static
     */
    public function addSelect(array|string ...$columns): static
    {
        $columns = is_array($columns[0] ?? null) ? $columns[0] : $columns;
        
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
     * @return static
     * 
     * @example
     * ->selectRaw('COUNT(*) as total')
     * ->selectRaw('tool_name, COUNT(*) as usage_count')
     */
    public function selectRaw(string $expression, array $bindings = []): static
    {
        $this->columns = [$expression];
        $this->selectRaw = true;
        if (!empty($bindings)) {
            $this->bindings = array_merge($this->bindings, $bindings);
        }
        return $this;
    }

    /**
     * DISTINCT
     * 
     * @return static
     */
    public function distinct(): static
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * WHERE 条件
     * 
     * @param string|array|Closure $column 列名或条件数组
     * @param mixed $operator 操作符或值
     * @param mixed $value 值
     * @param string $boolean 连接符（AND/OR）
     * @return static
     */
    public function where(string|array|Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): static
    {
        // 数组形式
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->where($key, '=', $val, $boolean);
            }
            return $this;
        }

        // 闭包（嵌套查询）
        if ($column instanceof Closure) {
            $nested = new static($this->model);
            $column($nested);
            $this->wheres[] = [
                'type' => 'nested',
                'wheres' => $nested->wheres,
                'boolean' => $boolean,
            ];
            $this->bindings = array_merge($this->bindings, $nested->bindings);
            return $this;
        }

        // 两参数形式
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => strtoupper($operator),
            'value' => $value,
            'boolean' => $boolean,
        ];
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * OR WHERE
     * 
     * @param string|array|Closure $column 列名
     * @param mixed $operator 操作符
     * @param mixed $value 值
     * @return static
     */
    public function orWhere(string|array|Closure $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * WHERE IN
     * 
     * @param string $column 列名
     * @param array $values 值数组
     * @param string $boolean 连接符
     * @param bool $not 是否取反
     * @return static
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): static
    {
        $this->wheres[] = [
            'type' => $not ? 'not_in' : 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
        ];
        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * WHERE NOT IN
     * 
     * @param string $column 列名
     * @param array $values 值数组
     * @return static
     */
    public function whereNotIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values, 'AND', true);
    }

    /**
     * WHERE NULL
     * 
     * @param string $column 列名
     * @param string $boolean 连接符
     * @param bool $not 是否取反
     * @return static
     */
    public function whereNull(string $column, string $boolean = 'AND', bool $not = false): static
    {
        $this->wheres[] = [
            'type' => $not ? 'not_null' : 'null',
            'column' => $column,
            'boolean' => $boolean,
        ];

        return $this;
    }

    /**
     * WHERE NOT NULL
     * 
     * @param string $column 列名
     * @return static
     */
    public function whereNotNull(string $column): static
    {
        return $this->whereNull($column, 'AND', true);
    }

    /**
     * WHERE BETWEEN
     * 
     * @param string $column 列名
     * @param array $values [min, max]
     * @param string $boolean 连接符
     * @param bool $not 是否取反
     * @return static
     */
    public function whereBetween(string $column, array $values, string $boolean = 'AND', bool $not = false): static
    {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'not' => $not,
            'boolean' => $boolean,
        ];
        $this->bindings[] = $values[0];
        $this->bindings[] = $values[1];

        return $this;
    }

    /**
     * WHERE LIKE
     * 
     * @param string $column 列名
     * @param string $value 值
     * @return static
     */
    public function whereLike(string $column, string $value): static
    {
        return $this->where($column, 'LIKE', $value);
    }

    /**
     * WHERE 日期
     * 
     * @param string $column 列名
     * @param string $value 日期值
     * @return static
     */
    public function whereDate(string $column, string $value): static
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => "DATE({$column}) = ?",
            'boolean' => 'AND',
        ];
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * WHERE 原始 SQL
     * 
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数
     * @return static
     */
    public function whereRaw(string $sql, array $bindings = []): static
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => 'AND',
        ];
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    /**
     * WHERE 关联存在
     * 
     * @param string $relation 关联名称
     * @param Closure|null $callback 约束回调
     * @return static
     */
    public function whereHas(string $relation, ?Closure $callback = null): static
    {
        // 简化实现：检查关联是否存在
        // 完整实现需要生成子查询
        if (method_exists($this->model, $relation)) {
            $relationInstance = $this->model->$relation();
            
            // 这里简化处理，实际应该生成 EXISTS 子查询
            $foreignKey = $relationInstance->getForeignKey();
            $this->whereNotNull($foreignKey);
        }

        return $this;
    }

    /**
     * WHERE 关联不存在
     * 
     * @param string $relation 关联名称
     * @param Closure|null $callback 约束回调
     * @return static
     */
    public function whereDoesntHave(string $relation, ?Closure $callback = null): static
    {
        if (method_exists($this->model, $relation)) {
            $relationInstance = $this->model->$relation();
            $foreignKey = $relationInstance->getForeignKey();
            $this->whereNull($foreignKey);
        }

        return $this;
    }

    /**
     * INNER JOIN
     * 
     * @param string $table 表名
     * @param string $first 第一列
     * @param string $operator 操作符
     * @param string $second 第二列
     * @return static
     */
    public function join(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * LEFT JOIN
     * 
     * @param string $table 表名
     * @param string $first 第一列
     * @param string $operator 操作符
     * @param string $second 第二列
     * @return static
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * RIGHT JOIN
     * 
     * @param string $table 表名
     * @param string $first 第一列
     * @param string $operator 操作符
     * @param string $second 第二列
     * @return static
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = [
            'type' => 'RIGHT',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * ORDER BY
     * 
     * @param string $column 列名
     * @param string $direction 方向
     * @return static
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction),
        ];

        return $this;
    }

    /**
     * ORDER BY DESC
     * 
     * @param string $column 列名
     * @return static
     */
    public function orderByDesc(string $column): static
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * 最新记录
     * 
     * @param string $column 列名
     * @return static
     */
    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * 最旧记录
     * 
     * @param string $column 列名
     * @return static
     */
    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'asc');
    }

    /**
     * 随机排序
     * 
     * @return static
     */
    public function inRandomOrder(): static
    {
        $this->orderBy[] = ['raw' => 'RAND()'];
        return $this;
    }

    /**
     * GROUP BY
     * 
     * @param string ...$columns 列名
     * @return static
     */
    public function groupBy(string ...$columns): static
    {
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    /**
     * HAVING
     * 
     * @param string $column 列名
     * @param string $operator 操作符
     * @param mixed $value 值
     * @return static
     */
    public function having(string $column, string $operator, mixed $value): static
    {
        $this->having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND',
        ];
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * LIMIT
     * 
     * @param int $limit 限制数量
     * @return static
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * take 别名
     * 
     * @param int $limit 限制数量
     * @return static
     */
    public function take(int $limit): static
    {
        return $this->limit($limit);
    }

    /**
     * OFFSET
     * 
     * @param int $offset 偏移量
     * @return static
     */
    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * skip 别名
     * 
     * @param int $offset 偏移量
     * @return static
     */
    public function skip(int $offset): static
    {
        return $this->offset($offset);
    }

    /**
     * 分页
     * 
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return static
     */
    public function forPage(int $page, int $perPage = 15): static
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }

    /**
     * 预加载关联
     * 
     * @param array|string $relations 关联名称
     * @return static
     */
    public function with(array|string $relations): static
    {
        $this->eagerLoad = array_merge(
            $this->eagerLoad,
            is_array($relations) ? $relations : func_get_args()
        );

        return $this;
    }

    /**
     * 关联计数
     * 
     * @param array|string $relations 关联名称
     * @return static
     */
    public function withCount(array|string $relations): static
    {
        $relations = is_array($relations) ? $relations : [$relations];
        
        foreach ($relations as $relation) {
            $this->addSelect("{$relation}_count");
            // 实际实现需要添加子查询
        }

        return $this;
    }

    // ==================== 执行方法 ====================

    /**
     * 获取所有结果
     * 
     * @return Collection
     */
    public function get(): Collection
    {
        $results = $this->runQuery();
        $models = $this->hydrate($results);

        // 预加载关联
        if (!empty($this->eagerLoad)) {
            $models = $this->eagerLoadRelations($models);
        }

        return new Collection($models);
    }

    /**
     * 获取原始数组结果（不转换为模型）
     * 
     * 适用于聚合查询、GROUP BY 等场景
     * 
     * @return array
     * 
     * @example
     * VipUsageLog::selectRaw('tool_name, COUNT(*) as usage_count')
     *     ->groupBy('tool_name')
     *     ->orderBy('usage_count', 'DESC')
     *     ->limit(10)
     *     ->getRaw();
     */
    public function getRaw(): array
    {
        return $this->runQuery();
    }

    /**
     * 获取第一条结果
     * 
     * @return Model|null
     */
    public function first(): ?Model
    {
        return $this->limit(1)->get()->first();
    }

    /**
     * 获取第一条或抛出异常
     * 
     * @return Model
     * @throws \RuntimeException
     */
    public function firstOrFail(): Model
    {
        $model = $this->first();

        if ($model === null) {
            throw new \RuntimeException('未找到记录');
        }

        return $model;
    }

    /**
     * 根据主键查找
     * 
     * @param mixed $id 主键值
     * @return Model|null
     */
    public function find(mixed $id): ?Model
    {
        return $this->where($this->model->getKeyName(), $id)->first();
    }

    /**
     * 获取单列值
     * 
     * @param string $column 列名
     * @return mixed
     */
    public function value(string $column): mixed
    {
        $result = $this->select($column)->first();
        return $result?->getAttribute($column);
    }

    /**
     * 获取单列数组
     * 
     * @param string $column 列名
     * @param string|null $key 键名
     * @return array
     */
    public function pluck(string $column, ?string $key = null): array
    {
        $results = $this->get();
        
        $plucked = [];
        foreach ($results as $model) {
            if ($key !== null) {
                $plucked[$model->getAttribute($key)] = $model->getAttribute($column);
            } else {
                $plucked[] = $model->getAttribute($column);
            }
        }

        return $plucked;
    }

    /**
     * 检查是否存在
     * 
     * @return bool
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * 检查是否不存在
     * 
     * @return bool
     */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    // ==================== 聚合方法 ====================

    /**
     * COUNT
     * 
     * @param string $column 列名
     * @return int
     */
    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('COUNT', $column);
    }

    /**
     * SUM
     * 
     * @param string $column 列名
     * @return float
     */
    public function sum(string $column): float
    {
        return (float) $this->aggregate('SUM', $column);
    }

    /**
     * AVG
     * 
     * @param string $column 列名
     * @return float
     */
    public function avg(string $column): float
    {
        return (float) $this->aggregate('AVG', $column);
    }

    /**
     * MAX
     * 
     * @param string $column 列名
     * @return mixed
     */
    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    /**
     * MIN
     * 
     * @param string $column 列名
     * @return mixed
     */
    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    /**
     * 执行聚合函数
     * 
     * @param string $function 函数名
     * @param string $column 列名
     * @return mixed
     */
    protected function aggregate(string $function, string $column): mixed
    {
        $this->columns = ["{$function}({$column}) as aggregate"];
        $results = $this->runQuery();

        return $results[0]['aggregate'] ?? null;
    }

    // ==================== 修改方法 ====================

    /**
     * 插入记录
     * 
     * @param array $values 值
     * @return int 插入的 ID
     */
    public function insert(array $values): int
    {
        return db()->table($this->model->getTable())->insert($values);
    }

    /**
     * 更新记录
     * 
     * @param array $values 值
     * @return int 影响的行数
     */
    public function update(array $values): int
    {
        $query = db()->table($this->model->getTable());
        
        // 应用 WHERE 条件
        $this->applyWheresToQuery($query);

        return $query->update($values);
    }

    /**
     * 删除记录
     * 
     * @return int 影响的行数
     */
    public function delete(): int
    {
        $query = db()->table($this->model->getTable());
        
        // 应用 WHERE 条件
        $this->applyWheresToQuery($query);

        return $query->delete();
    }

    /**
     * 自增
     * 
     * @param string $column 列名
     * @param int $amount 数量
     * @param array $extra 额外更新
     * @return int
     */
    public function increment(string $column, int $amount = 1, array $extra = []): int
    {
        $query = db()->table($this->model->getTable());
        $this->applyWheresToQuery($query);
        
        return $query->increment($column, $amount, $extra);
    }

    /**
     * 自减
     * 
     * @param string $column 列名
     * @param int $amount 数量
     * @param array $extra 额外更新
     * @return int
     */
    public function decrement(string $column, int $amount = 1, array $extra = []): int
    {
        $query = db()->table($this->model->getTable());
        $this->applyWheresToQuery($query);
        
        return $query->decrement($column, $amount, $extra);
    }

    // ==================== 内部方法 ====================

    /**
     * 执行查询
     * 
     * @return array
     */
    protected function runQuery(): array
    {
        $query = db()->table($this->model->getTable());

        // 应用 SELECT
        if ($this->selectRaw) {
            // 原生表达式
            $query->selectRaw($this->columns[0]);
        } elseif ($this->columns !== ['*']) {
            $query->select(...$this->columns);
        }

        // 应用 DISTINCT
        if ($this->distinct) {
            $query->distinct();
        }

        // 应用 JOIN
        foreach ($this->joins as $join) {
            $method = strtolower($join['type']) . 'Join';
            if ($method === 'innerJoin') {
                $method = 'join';
            }
            $query->$method($join['table'], $join['first'], $join['operator'], $join['second']);
        }

        // 应用 WHERE
        $this->applyWheresToQuery($query);

        // 应用 GROUP BY
        if (!empty($this->groupBy)) {
            $query->groupBy(...$this->groupBy);
        }

        // 应用 HAVING
        foreach ($this->having as $having) {
            $query->having($having['column'], $having['operator'], $having['value']);
        }

        // 应用 ORDER BY
        foreach ($this->orderBy as $order) {
            if (isset($order['raw'])) {
                $query->orderByRaw($order['raw']);
            } else {
                $query->orderBy($order['column'], $order['direction']);
            }
        }

        // 应用 LIMIT 和 OFFSET
        if ($this->limit !== null) {
            $query->limit($this->limit);
        }
        if ($this->offset !== null) {
            $query->offset($this->offset);
        }

        return $query->get();
    }

    /**
     * 应用 WHERE 条件到查询
     * 
     * @param object $query 查询构建器
     * @return void
     */
    protected function applyWheresToQuery(object $query): void
    {
        foreach ($this->wheres as $where) {
            $type = $where['type'];
            $boolean = strtolower($where['boolean'] ?? 'and');

            switch ($type) {
                case 'basic':
                    $method = $boolean === 'or' ? 'orWhere' : 'where';
                    $query->$method($where['column'], $where['operator'], $where['value']);
                    break;

                case 'in':
                    $query->whereIn($where['column'], $where['values']);
                    break;

                case 'not_in':
                    $query->whereNotIn($where['column'], $where['values']);
                    break;

                case 'null':
                    $query->whereNull($where['column']);
                    break;

                case 'not_null':
                    $query->whereNotNull($where['column']);
                    break;

                case 'between':
                    $method = $where['not'] ? 'whereNotBetween' : 'whereBetween';
                    $query->$method($where['column'], $where['values']);
                    break;

                case 'raw':
                    $query->whereRaw($where['sql']);
                    break;
            }
        }
    }

    /**
     * 将数据库结果转换为模型实例
     * 
     * @param array $results 数据库结果
     * @return array 模型数组
     */
    protected function hydrate(array $results): array
    {
        $models = [];

        foreach ($results as $result) {
            $models[] = $this->model->newFromBuilder($result);
        }

        return $models;
    }

    /**
     * 预加载关联
     * 
     * @param array $models 模型数组
     * @return Collection
     */
    protected function eagerLoadRelations(array $models): Collection
    {
        foreach ($this->eagerLoad as $name => $constraints) {
            if (is_numeric($name)) {
                $name = $constraints;
                $constraints = null;
            }

            // 加载每个模型的关联
            foreach ($models as $model) {
                $model->load($name);
            }
        }

        return new Collection($models);
    }

    /**
     * 获取模型实例
     * 
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * 动态调用模型方法
     * 
     * @param string $method 方法名
     * @param array $parameters 参数
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        // 调用模型的本地作用域
        $scopeMethod = 'scope' . ucfirst($method);
        if (method_exists($this->model, $scopeMethod)) {
            array_unshift($parameters, $this);
            return $this->model->$scopeMethod(...$parameters) ?? $this;
        }

        throw new \BadMethodCallException("方法 [{$method}] 不存在");
    }
}
