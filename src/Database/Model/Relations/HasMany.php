<?php

/**
 * HolySword Framework - 一对多关联关系
 * 
 * 定义一对多的关联关系，如 User hasMany Orders。
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
 * 一对多关联关系
 * 
 * @package HolySword\Database\Model\Relations
 */
class HasMany extends Relation
{
    /**
     * 外键名
     * 
     * @var string
     */
    protected string $foreignKey;

    /**
     * 本地键名
     * 
     * @var string
     */
    protected string $localKey;

    /**
     * 创建一对多关联实例
     * 
     * @param Model $related 关联模型
     * @param Model $parent 父模型
     * @param string $foreignKey 外键
     * @param string $localKey 本地键
     */
    public function __construct(Model $related, Model $parent, string $foreignKey, string $localKey)
    {
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;

        parent::__construct($related, $parent);
    }

    /**
     * 添加基本约束条件
     * 
     * @return void
     */
    public function addConstraints(): void
    {
        if (static::$constraints) {
            $parentKey = $this->parent->getAttribute($this->localKey);
            
            $this->query->where($this->foreignKey, '=', $parentKey);
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
        $keys = $this->getKeys($models, $this->localKey);

        $this->query->whereIn($this->foreignKey, $keys);
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
        $dictionary = $this->buildDictionary($results, $this->foreignKey);

        foreach ($models as $model) {
            $key = $model->getAttribute($this->localKey);
            
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
        $parentKey = $this->parent->getAttribute($this->localKey);
        
        if ($parentKey === null) {
            return new Collection([]);
        }

        return $this->query->get();
    }

    /**
     * 获取外键名
     * 
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    /**
     * 获取本地键名
     * 
     * @return string
     */
    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    /**
     * 获取限定的外键名
     * 
     * @return string
     */
    public function getQualifiedForeignKeyName(): string
    {
        return $this->qualifyColumn($this->foreignKey);
    }

    /**
     * 为创建设置外键属性
     * 
     * @param Model $model 模型实例
     * @return void
     */
    protected function setForeignAttributesForCreate(Model $model): void
    {
        $model->setAttribute(
            $this->foreignKey,
            $this->parent->getAttribute($this->localKey)
        );
    }

    /**
     * 保存关联模型
     * 
     * @param Model $model 模型实例
     * @return Model
     */
    public function save(Model $model): Model
    {
        $this->setForeignAttributesForCreate($model);
        
        $model->save();
        
        return $model;
    }

    /**
     * 批量保存关联模型
     * 
     * @param iterable $models 模型列表
     * @return array
     */
    public function saveMany(iterable $models): array
    {
        $saved = [];

        foreach ($models as $model) {
            $saved[] = $this->save($model);
        }

        return $saved;
    }

    /**
     * 根据条件查找或创建
     * 
     * @param array $attributes 查询条件
     * @param array $values 额外属性
     * @return Model
     */
    public function firstOrNew(array $attributes, array $values = []): Model
    {
        $instance = $this->where($attributes)->first();

        if ($instance !== null) {
            return $instance;
        }

        return $this->make(array_merge($attributes, $values));
    }

    /**
     * 根据条件查找或创建并保存
     * 
     * @param array $attributes 查询条件
     * @param array $values 额外属性
     * @return Model
     */
    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        $instance = $this->where($attributes)->first();

        if ($instance !== null) {
            return $instance;
        }

        return $this->create(array_merge($attributes, $values));
    }

    /**
     * 更新或创建
     * 
     * @param array $attributes 查询条件
     * @param array $values 更新值
     * @return Model
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        $instance = $this->where($attributes)->first();

        if ($instance !== null) {
            $instance->fill($values);
            $instance->save();
            return $instance;
        }

        return $this->create(array_merge($attributes, $values));
    }

    /**
     * 查找指定ID的关联模型
     * 
     * @param mixed $id ID
     * @param array $columns 列
     * @return Model|Collection|null
     */
    public function find(mixed $id, array $columns = ['*']): Model|Collection|null
    {
        if (is_array($id)) {
            return $this->findMany($id, $columns);
        }

        return $this->query->find($id, $columns);
    }

    /**
     * 查找多个关联模型
     * 
     * @param array $ids ID数组
     * @param array $columns 列
     * @return Collection
     */
    public function findMany(array $ids, array $columns = ['*']): Collection
    {
        if (empty($ids)) {
            return new Collection([]);
        }

        return $this->query
            ->whereIn($this->related->getKeyName(), $ids)
            ->get($columns);
    }

    /**
     * 查找或失败
     * 
     * @param mixed $id ID
     * @param array $columns 列
     * @return Model
     * @throws \RuntimeException
     */
    public function findOrFail(mixed $id, array $columns = ['*']): Model
    {
        $result = $this->find($id, $columns);

        if ($result === null) {
            throw new \RuntimeException(
                sprintf('No query results for model [%s] %s', get_class($this->related), $id)
            );
        }

        return $result;
    }
}
