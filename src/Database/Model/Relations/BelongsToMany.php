<?php

/**
 * HolySword Framework - 多对多关联关系
 * 
 * 定义多对多的关联关系，如 User belongsToMany Roles。
 * 
 * @package    HolySword
 * @subpackage Database\Model\Relations
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Database\Model\Relations;

use HolySword\Database\Model\Model;
use HolySword\Database\Model\Collection;

/**
 * 多对多关联关系
 * 
 * @package HolySword\Database\Model\Relations
 */
class BelongsToMany extends Relation
{
    /**
     * 中间表名
     * 
     * @var string
     */
    protected string $table;

    /**
     * 父模型在中间表的外键
     * 
     * @var string
     */
    protected string $foreignPivotKey;

    /**
     * 关联模型在中间表的外键
     * 
     * @var string
     */
    protected string $relatedPivotKey;

    /**
     * 父模型的主键
     * 
     * @var string
     */
    protected string $parentKey;

    /**
     * 关联模型的主键
     * 
     * @var string
     */
    protected string $relatedKey;

    /**
     * 要查询的中间表列
     * 
     * @var array
     */
    protected array $pivotColumns = [];

    /**
     * 中间表的额外条件
     * 
     * @var array
     */
    protected array $pivotWheres = [];

    /**
     * 中间表访问器名称
     * 
     * @var string
     */
    protected string $accessor = 'pivot';

    /**
     * 创建多对多关联实例
     * 
     * @param Model $related 关联模型
     * @param Model $parent 父模型
     * @param string $table 中间表名
     * @param string $foreignPivotKey 父模型外键
     * @param string $relatedPivotKey 关联模型外键
     * @param string $parentKey 父模型主键
     * @param string $relatedKey 关联模型主键
     */
    public function __construct(
        Model $related,
        Model $parent,
        string $table,
        string $foreignPivotKey,
        string $relatedPivotKey,
        string $parentKey,
        string $relatedKey
    ) {
        $this->table = $table;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
        $this->parentKey = $parentKey;
        $this->relatedKey = $relatedKey;

        parent::__construct($related, $parent);
    }

    /**
     * 添加基本约束条件
     * 
     * @return void
     */
    public function addConstraints(): void
    {
        $this->performJoin();

        if (static::$constraints) {
            $this->addWhereConstraints();
        }
    }

    /**
     * 执行中间表连接
     * 
     * @return void
     */
    protected function performJoin(): void
    {
        $relatedTable = $this->related->getTable();
        
        $this->query->join(
            $this->table,
            "{$this->table}.{$this->relatedPivotKey}",
            '=',
            "{$relatedTable}.{$this->relatedKey}"
        );
    }

    /**
     * 添加 WHERE 约束条件
     * 
     * @return void
     */
    protected function addWhereConstraints(): void
    {
        $parentKey = $this->parent->getAttribute($this->parentKey);
        
        $this->query->where(
            "{$this->table}.{$this->foreignPivotKey}",
            '=',
            $parentKey
        );

        // 添加中间表的额外条件
        foreach ($this->pivotWheres as $where) {
            $this->query->where(
                "{$this->table}.{$where['column']}",
                $where['operator'],
                $where['value']
            );
        }
    }

    /**
     * 添加预加载约束条件
     * 
     * @param array $models 父模型数组
     * @return void
     */
    public function addEagerConstraints(array $models): void
    {
        $keys = $this->getKeys($models, $this->parentKey);

        $this->query->whereIn(
            "{$this->table}.{$this->foreignPivotKey}",
            $keys
        );
    }

    /**
     * 初始化关联（用于预加载）
     * 
     * @param array $models 父模型数组
     * @param string $relation 关联名称
     * @return array
     */
    public function initRelation(array $models, string $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, new Collection([]));
        }

        return $models;
    }

    /**
     * 匹配预加载结果到父模型
     * 
     * @param array $models 父模型数组
     * @param Collection $results 关联结果集合
     * @param string $relation 关联名称
     * @return array
     */
    public function match(array $models, Collection $results, string $relation): array
    {
        $dictionary = $this->buildDictionary($results, 'pivot_' . $this->foreignPivotKey);

        foreach ($models as $model) {
            $key = $model->getAttribute($this->parentKey);
            
            $items = $dictionary[$key] ?? [];
            $model->setRelation($relation, new Collection($items));
        }

        return $models;
    }

    /**
     * 获取关联结果
     * 
     * @return Collection
     */
    public function getResults(): Collection
    {
        $parentKey = $this->parent->getAttribute($this->parentKey);
        
        if ($parentKey === null) {
            return new Collection([]);
        }

        return $this->get();
    }

    /**
     * 执行查询
     * 
     * @param array $columns 要查询的列
     * @return Collection
     */
    public function get(array $columns = ['*']): Collection
    {
        // 添加中间表列到查询
        $columns = $this->addPivotColumns($columns);
        
        $results = $this->query->get($columns);

        // 处理中间表数据
        $this->hydratePivotRelation($results);

        return $results;
    }

    /**
     * 添加中间表列到查询
     * 
     * @param array $columns 列
     * @return array
     */
    protected function addPivotColumns(array $columns): array
    {
        // 如果是 *，需要显式指定关联表的列
        if ($columns === ['*']) {
            $columns = [$this->related->getTable() . '.*'];
        }

        // 添加中间表的外键列
        $pivotColumns = [
            "{$this->table}.{$this->foreignPivotKey} as pivot_{$this->foreignPivotKey}",
            "{$this->table}.{$this->relatedPivotKey} as pivot_{$this->relatedPivotKey}",
        ];

        // 添加用户指定的中间表列
        foreach ($this->pivotColumns as $column) {
            $pivotColumns[] = "{$this->table}.{$column} as pivot_{$column}";
        }

        return array_merge($columns, $pivotColumns);
    }

    /**
     * 处理中间表数据
     * 
     * @param Collection $results 结果集
     * @return void
     */
    protected function hydratePivotRelation(Collection $results): void
    {
        foreach ($results as $model) {
            $pivotAttributes = [];
            $attributes = $model->getAttributes();

            foreach ($attributes as $key => $value) {
                if (str_starts_with($key, 'pivot_')) {
                    $pivotKey = substr($key, 6); // 移除 'pivot_' 前缀
                    $pivotAttributes[$pivotKey] = $value;
                    
                    // 从模型属性中移除
                    $model->offsetUnset($key);
                }
            }

            // 将中间表数据设置为关联属性
            $model->setRelation($this->accessor, (object) $pivotAttributes);
        }
    }

    /**
     * 设置中间表访问器名称
     * 
     * @param string $accessor 访问器名称
     * @return static
     */
    public function as(string $accessor): static
    {
        $this->accessor = $accessor;
        return $this;
    }

    /**
     * 设置要查询的中间表列
     * 
     * @param array|string $columns 列名
     * @return static
     */
    public function withPivot(array|string $columns): static
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        
        $this->pivotColumns = array_merge($this->pivotColumns, $columns);

        return $this;
    }

    /**
     * 添加中间表条件
     * 
     * @param string $column 列名
     * @param mixed $operator 操作符或值
     * @param mixed $value 值
     * @return static
     */
    public function wherePivot(string $column, mixed $operator = null, mixed $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->pivotWheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * 添加中间表IN条件
     * 
     * @param string $column 列名
     * @param array $values 值数组
     * @return static
     */
    public function wherePivotIn(string $column, array $values): static
    {
        $this->query->whereIn("{$this->table}.{$column}", $values);

        return $this;
    }

    /**
     * 添加中间表NOT IN条件
     * 
     * @param string $column 列名
     * @param array $values 值数组
     * @return static
     */
    public function wherePivotNotIn(string $column, array $values): static
    {
        $this->query->whereNotIn("{$this->table}.{$column}", $values);

        return $this;
    }

    /**
     * 附加关联（添加到中间表）
     * 
     * @param mixed $id 要附加的ID或模型
     * @param array $attributes 中间表额外属性
     * @return void
     */
    public function attach(mixed $id, array $attributes = []): void
    {
        $ids = $this->parseIds($id);

        foreach ($ids as $relatedId => $attrs) {
            if (is_numeric($relatedId)) {
                $relatedId = $attrs;
                $attrs = [];
            }

            $record = array_merge([
                $this->foreignPivotKey => $this->parent->getAttribute($this->parentKey),
                $this->relatedPivotKey => $relatedId,
            ], $attributes, is_array($attrs) ? $attrs : []);

            db()->table($this->table)->insert($record);
        }
    }

    /**
     * 分离关联（从中间表删除）
     * 
     * @param mixed $ids 要分离的ID
     * @return int 删除的记录数
     */
    public function detach(mixed $ids = null): int
    {
        $query = db()->table($this->table)
            ->where($this->foreignPivotKey, $this->parent->getAttribute($this->parentKey));

        if ($ids !== null) {
            $ids = is_array($ids) ? $ids : [$ids];
            
            // 支持模型实例
            $ids = array_map(function ($id) {
                return $id instanceof Model ? $id->getKey() : $id;
            }, $ids);

            $query->whereIn($this->relatedPivotKey, $ids);
        }

        return $query->delete();
    }

    /**
     * 同步关联（完全替换中间表记录）
     * 
     * @param mixed $ids 要同步的ID
     * @param bool $detaching 是否分离不存在的记录
     * @return array 包含 attached、detached、updated 的数组
     */
    public function sync(mixed $ids, bool $detaching = true): array
    {
        $changes = [
            'attached' => [],
            'detached' => [],
            'updated' => [],
        ];

        $current = $this->getCurrentlyAttachedPivots();
        $records = $this->formatRecordsList($this->parseIds($ids));

        // 分离不再需要的记录
        if ($detaching) {
            $detach = array_diff($current, array_keys($records));

            if (!empty($detach)) {
                $this->detach($detach);
                $changes['detached'] = array_values($detach);
            }
        }

        // 附加新记录或更新现有记录
        foreach ($records as $id => $attributes) {
            if (in_array($id, $current)) {
                // 更新现有记录
                if (!empty($attributes)) {
                    $this->updateExistingPivot($id, $attributes);
                    $changes['updated'][] = $id;
                }
            } else {
                // 附加新记录
                $this->attach($id, $attributes);
                $changes['attached'][] = $id;
            }
        }

        return $changes;
    }

    /**
     * 同步但不分离
     * 
     * @param mixed $ids 要同步的ID
     * @return array
     */
    public function syncWithoutDetaching(mixed $ids): array
    {
        return $this->sync($ids, false);
    }

    /**
     * 切换关联状态
     * 
     * @param mixed $ids ID
     * @return array
     */
    public function toggle(mixed $ids): array
    {
        $changes = [
            'attached' => [],
            'detached' => [],
        ];

        $current = $this->getCurrentlyAttachedPivots();
        $ids = $this->parseIds($ids);

        foreach ($ids as $id => $attributes) {
            if (is_numeric($id)) {
                $id = $attributes;
                $attributes = [];
            }

            if (in_array($id, $current)) {
                $this->detach($id);
                $changes['detached'][] = $id;
            } else {
                $this->attach($id, is_array($attributes) ? $attributes : []);
                $changes['attached'][] = $id;
            }
        }

        return $changes;
    }

    /**
     * 更新中间表记录
     * 
     * @param mixed $id 关联模型ID
     * @param array $attributes 属性
     * @return int
     */
    public function updateExistingPivot(mixed $id, array $attributes): int
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        return db()->table($this->table)
            ->where($this->foreignPivotKey, $this->parent->getAttribute($this->parentKey))
            ->where($this->relatedPivotKey, $id)
            ->update($attributes);
    }

    /**
     * 获取当前已附加的中间表ID
     * 
     * @return array
     */
    protected function getCurrentlyAttachedPivots(): array
    {
        $results = db()->table($this->table)
            ->where($this->foreignPivotKey, $this->parent->getAttribute($this->parentKey))
            ->get([$this->relatedPivotKey]);

        return array_column($results, $this->relatedPivotKey);
    }

    /**
     * 解析ID
     * 
     * @param mixed $value 值
     * @return array
     */
    protected function parseIds(mixed $value): array
    {
        if ($value instanceof Model) {
            return [$value->getKey()];
        }

        if ($value instanceof Collection) {
            return $value->pluck($this->relatedKey)->all();
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * 格式化记录列表
     * 
     * @param array $records 记录
     * @return array
     */
    protected function formatRecordsList(array $records): array
    {
        $formatted = [];

        foreach ($records as $id => $attributes) {
            if (is_numeric($id)) {
                $formatted[$attributes] = [];
            } else {
                $formatted[$id] = is_array($attributes) ? $attributes : [];
            }
        }

        return $formatted;
    }

    /**
     * 获取中间表名
     * 
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * 获取父模型外键
     * 
     * @return string
     */
    public function getForeignPivotKey(): string
    {
        return $this->foreignPivotKey;
    }

    /**
     * 获取关联模型外键
     * 
     * @return string
     */
    public function getRelatedPivotKey(): string
    {
        return $this->relatedPivotKey;
    }

    /**
     * 获取父模型主键
     * 
     * @return string
     */
    public function getParentKey(): string
    {
        return $this->parentKey;
    }

    /**
     * 获取关联模型主键
     * 
     * @return string
     */
    public function getRelatedKeyName(): string
    {
        return $this->relatedKey;
    }

    /**
     * 获取限定的父模型外键
     * 
     * @return string
     */
    public function getQualifiedForeignPivotKeyName(): string
    {
        return $this->table . '.' . $this->foreignPivotKey;
    }

    /**
     * 获取限定的关联模型外键
     * 
     * @return string
     */
    public function getQualifiedRelatedPivotKeyName(): string
    {
        return $this->table . '.' . $this->relatedPivotKey;
    }

    /**
     * 获取限定的父模型主键
     * 
     * @return string
     */
    public function getQualifiedParentKeyName(): string
    {
        return $this->parent->getTable() . '.' . $this->parentKey;
    }
}
