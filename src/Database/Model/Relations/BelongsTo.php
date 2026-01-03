<?php

/**
 * HolySword Framework - 反向关联关系（多对一/一对一）
 * 
 * 定义反向关联关系，如 Order belongsTo User。
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
 * 反向关联关系
 * 
 * @package HolySword\Database\Model\Relations
 */
class BelongsTo extends Relation
{
    /**
     * 外键名（在子模型上的列）
     * 
     * @var string
     */
    protected string $foreignKey;

    /**
     * 父模型的主键名
     * 
     * @var string
     */
    protected string $ownerKey;

    /**
     * 关联名称
     * 
     * @var string
     */
    protected string $relationName;

    /**
     * 子模型实例（持有外键的模型）
     * 
     * @var Model
     */
    protected Model $child;

    /**
     * 创建反向关联实例
     * 
     * @param Model $related 关联的父模型
     * @param Model $child 子模型（持有外键）
     * @param string $foreignKey 外键名
     * @param string $ownerKey 父模型主键名
     */
    public function __construct(Model $related, Model $child, string $foreignKey, string $ownerKey)
    {
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;
        $this->child = $child;

        parent::__construct($related, $child);
    }

    /**
     * 添加基本约束条件
     * 
     * @return void
     */
    public function addConstraints(): void
    {
        if (static::$constraints) {
            $foreignKeyValue = $this->child->getAttribute($this->foreignKey);
            
            $this->query->where(
                $this->related->getTable() . '.' . $this->ownerKey,
                '=',
                $foreignKeyValue
            );
        }
    }

    /**
     * 添加预加载约束条件
     * 
     * @param array $models 子模型数组
     * @return void
     */
    public function addEagerConstraints(array $models): void
    {
        $keys = $this->getKeys($models, $this->foreignKey);

        $this->query->whereIn(
            $this->related->getTable() . '.' . $this->ownerKey,
            $keys
        );
    }

    /**
     * 初始化关联（用于预加载）
     * 
     * @param array $models 子模型数组
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
     * 匹配预加载结果到子模型
     * 
     * @param array $models 子模型数组
     * @param Collection $results 关联结果集合
     * @param string $relation 关联名称
     * @return array
     */
    public function match(array $models, Collection $results, string $relation): array
    {
        $dictionary = $this->buildDictionary($results, $this->ownerKey);

        foreach ($models as $model) {
            $key = $model->getAttribute($this->foreignKey);
            
            if (isset($dictionary[$key])) {
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
        $foreignKeyValue = $this->child->getAttribute($this->foreignKey);
        
        if ($foreignKeyValue === null) {
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
     * 获取父模型主键名
     * 
     * @return string
     */
    public function getOwnerKey(): string
    {
        return $this->ownerKey;
    }

    /**
     * 获取限定的外键名
     * 
     * @return string
     */
    public function getQualifiedForeignKeyName(): string
    {
        return $this->child->getTable() . '.' . $this->foreignKey;
    }

    /**
     * 获取限定的父模型主键名
     * 
     * @return string
     */
    public function getQualifiedOwnerKeyName(): string
    {
        return $this->related->getTable() . '.' . $this->ownerKey;
    }

    /**
     * 关联一个模型
     * 
     * @param Model|int|string|null $model 要关联的模型或ID
     * @return Model
     */
    public function associate(Model|int|string|null $model): Model
    {
        $ownerKey = $model instanceof Model ? $model->getAttribute($this->ownerKey) : $model;

        $this->child->setAttribute($this->foreignKey, $ownerKey);

        if ($model instanceof Model) {
            $this->child->setRelation($this->getRelationName(), $model);
        }

        return $this->child;
    }

    /**
     * 解除关联
     * 
     * @return Model
     */
    public function dissociate(): Model
    {
        $this->child->setAttribute($this->foreignKey, null);
        $this->child->setRelation($this->getRelationName(), null);

        return $this->child;
    }

    /**
     * 获取默认值（当关联不存在时）
     * 
     * @param Model|callable|array|null $callback 默认值或回调
     * @return static
     */
    public function withDefault(Model|callable|array|null $callback = null): static
    {
        // 存储默认值配置，在getResults中使用
        $this->withDefault = $callback;

        return $this;
    }

    /**
     * 获取关联结果（带默认值支持）
     * 
     * @return Model|null
     */
    public function getResultsWithDefault(): Model
    {
        $result = $this->getResults();

        if ($result !== null) {
            return $result;
        }

        // 返回默认值
        if (isset($this->withDefault)) {
            if ($this->withDefault instanceof Model) {
                return $this->withDefault;
            }

            if (is_callable($this->withDefault)) {
                return ($this->withDefault)($this->child) ?? $this->related->newInstance();
            }

            if (is_array($this->withDefault)) {
                return $this->related->newInstance($this->withDefault);
            }
        }

        return $this->related->newInstance();
    }

    /**
     * 检查是否匹配指定模型
     * 
     * @param Model|null $model 要比较的模型
     * @return bool
     */
    public function is(?Model $model): bool
    {
        if ($model === null) {
            return false;
        }

        return $this->child->getAttribute($this->foreignKey) === $model->getAttribute($this->ownerKey)
            && $this->related->getTable() === $model->getTable();
    }

    /**
     * 检查是否不匹配指定模型
     * 
     * @param Model|null $model 要比较的模型
     * @return bool
     */
    public function isNot(?Model $model): bool
    {
        return !$this->is($model);
    }

    /**
     * 获取子模型
     * 
     * @return Model
     */
    public function getChild(): Model
    {
        return $this->child;
    }
}
