<?php

/**
 * HolySword Framework - 关联关系基类
 * 
 * 所有关联关系类型的抽象基类。
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
use HolySword\Database\Model\Builder;
use HolySword\Database\Model\Collection;

/**
 * 关联关系基类
 * 
 * @package HolySword\Database\Model\Relations
 */
abstract class Relation
{
    /**
     * 关联模型实例
     * 
     * @var Model
     */
    protected Model $related;

    /**
     * 父模型实例
     * 
     * @var Model
     */
    protected Model $parent;

    /**
     * 查询构建器
     * 
     * @var Builder
     */
    protected Builder $query;

    /**
     * 是否包含默认约束
     * 
     * @var bool
     */
    protected static bool $constraints = true;

    /**
     * 创建关联实例
     * 
     * @param Model $related 关联模型实例
     * @param Model $parent 父模型实例
     */
    public function __construct(Model $related, Model $parent)
    {
        $this->related = $related;
        $this->parent = $parent;
        $this->query = $related->newQuery();

        // 添加默认约束条件
        $this->addConstraints();
    }

    /**
     * 添加基本约束条件
     * 
     * @return void
     */
    abstract public function addConstraints(): void;

    /**
     * 添加预加载约束条件
     * 
     * @param array $models 父模型数组
     * @return void
     */
    abstract public function addEagerConstraints(array $models): void;

    /**
     * 初始化关联（用于预加载）
     * 
     * @param array $models 父模型数组
     * @param string $relation 关联名称
     * @return array
     */
    abstract public function initRelation(array $models, string $relation): array;

    /**
     * 匹配预加载结果到父模型
     * 
     * @param array $models 父模型数组
     * @param Collection $results 关联结果集合
     * @param string $relation 关联名称
     * @return array
     */
    abstract public function match(array $models, Collection $results, string $relation): array;

    /**
     * 获取关联结果
     * 
     * @return mixed
     */
    abstract public function getResults(): mixed;

    /**
     * 获取关联查询构建器
     * 
     * @return Builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * 获取关联模型
     * 
     * @return Model
     */
    public function getRelated(): Model
    {
        return $this->related;
    }

    /**
     * 获取父模型
     * 
     * @return Model
     */
    public function getParent(): Model
    {
        return $this->parent;
    }

    /**
     * 执行查询获取结果
     * 
     * @param array $columns 要查询的列
     * @return Collection
     */
    public function get(array $columns = ['*']): Collection
    {
        return $this->query->get($columns);
    }

    /**
     * 获取第一条记录
     * 
     * @param array $columns 要查询的列
     * @return Model|null
     */
    public function first(array $columns = ['*']): ?Model
    {
        return $this->query->first($columns);
    }

    /**
     * 统计数量
     * 
     * @param string $column 列名
     * @return int
     */
    public function count(string $column = '*'): int
    {
        return $this->query->count($column);
    }

    /**
     * 检查是否存在关联记录
     * 
     * @return bool
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * 获取关联的外键数组
     * 
     * @param array $models 模型数组
     * @param string $key 键名
     * @return array
     */
    protected function getKeys(array $models, string $key): array
    {
        $keys = [];
        foreach ($models as $model) {
            $value = $model->getAttribute($key);
            if ($value !== null) {
                $keys[] = $value;
            }
        }
        return array_unique($keys);
    }

    /**
     * 设置是否应用约束
     * 
     * @param bool $constraints
     * @return void
     */
    public static function noConstraints(callable $callback): mixed
    {
        $previous = static::$constraints;
        static::$constraints = false;

        try {
            return $callback();
        } finally {
            static::$constraints = $previous;
        }
    }

    /**
     * 代理查询方法到查询构建器
     * 
     * @param string $method 方法名
     * @param array $parameters 参数
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        $result = $this->query->$method(...$parameters);

        // 如果返回的是Builder实例，返回当前Relation以支持链式调用
        if ($result instanceof Builder) {
            return $this;
        }

        return $result;
    }

    /**
     * 获取用于预加载的关联名
     * 
     * @return string
     */
    public function getRelationName(): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        
        foreach ($backtrace as $trace) {
            if (isset($trace['function']) && !in_array($trace['function'], [
                'getRelationName',
                'hasOne',
                'hasMany',
                'belongsTo',
                'belongsToMany',
            ])) {
                return $trace['function'];
            }
        }

        return '';
    }

    /**
     * 将值转换为数组
     * 
     * @param mixed $value
     * @return array
     */
    protected function buildDictionary(Collection $results, string $key): array
    {
        $dictionary = [];

        foreach ($results as $result) {
            $keyValue = $result->getAttribute($key);
            
            if (!isset($dictionary[$keyValue])) {
                $dictionary[$keyValue] = [];
            }
            
            $dictionary[$keyValue][] = $result;
        }

        return $dictionary;
    }

    /**
     * 获取限定的外键名
     * 
     * @param string $column 列名
     * @return string
     */
    protected function qualifyColumn(string $column): string
    {
        if (str_contains($column, '.')) {
            return $column;
        }

        return $this->related->getTable() . '.' . $column;
    }

    /**
     * 创建新的关联模型实例
     * 
     * @param array $attributes 属性
     * @return Model
     */
    public function make(array $attributes = []): Model
    {
        return $this->related->newInstance($attributes);
    }

    /**
     * 创建并保存关联模型
     * 
     * @param array $attributes 属性
     * @return Model
     */
    public function create(array $attributes = []): Model
    {
        $instance = $this->make($attributes);
        
        $this->setForeignAttributesForCreate($instance);
        
        $instance->save();
        
        return $instance;
    }

    /**
     * 为创建设置外键属性
     * 
     * @param Model $model 模型实例
     * @return void
     */
    protected function setForeignAttributesForCreate(Model $model): void
    {
        // 子类实现
    }

    /**
     * 批量创建关联模型
     * 
     * @param array $records 记录数组
     * @return array
     */
    public function createMany(array $records): array
    {
        $instances = [];

        foreach ($records as $record) {
            $instances[] = $this->create($record);
        }

        return $instances;
    }

    /**
     * 更新关联记录
     * 
     * @param array $attributes 属性
     * @return int
     */
    public function update(array $attributes): int
    {
        return $this->query->update($attributes);
    }

    /**
     * 删除关联记录
     * 
     * @return int
     */
    public function delete(): int
    {
        return $this->query->delete();
    }
}
