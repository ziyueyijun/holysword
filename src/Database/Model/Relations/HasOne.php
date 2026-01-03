<?php

/**
 * HolySword Framework - 一对一关联关系
 * 
 * 定义一对一的关联关系，如 User hasOne Profile。
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
 * 一对一关联关系
 * 
 * @package HolySword\Database\Model\Relations
 */
class HasOne extends Relation
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
     * 创建一对一关联实例
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
            $model->setRelation($relation, null);
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
            
            if (isset($dictionary[$key])) {
                // 一对一只取第一个
                $model->setRelation($relation, $dictionary[$key][0] ?? null);
            }
        }

        return $models;
    }

    /**
     * 获取关联结果
     * 
     * @return Model|null
     */
    public function getResults(): ?Model
    {
        $parentKey = $this->parent->getAttribute($this->localKey);
        
        if ($parentKey === null) {
            return null;
        }

        return $this->query->first();
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
     * 创建或更新关联模型
     * 
     * @param array $attributes 属性
     * @return Model
     */
    public function updateOrCreate(array $attributes): Model
    {
        $existing = $this->first();

        if ($existing !== null) {
            $existing->fill($attributes);
            $existing->save();
            return $existing;
        }

        return $this->create($attributes);
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
     * 关联模型是否存在
     * 
     * @return bool
     */
    public function is(?Model $model): bool
    {
        if ($model === null) {
            return false;
        }

        return $this->query->where(
            $this->related->getKeyName(),
            '=',
            $model->getKey()
        )->exists();
    }

    /**
     * 关联模型是否不存在
     * 
     * @return bool
     */
    public function isNot(?Model $model): bool
    {
        return !$this->is($model);
    }
}
