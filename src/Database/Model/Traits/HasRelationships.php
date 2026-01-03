<?php

/**
 * HolySword Framework - 模型关联关系 Trait
 * 
 * 提供模型之间关联关系的定义和查询功能。
 * 
 * @package    HolySword
 * @subpackage Database\Model\Traits
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Database\Model\Traits;

use HolySword\Database\Model\Relations\HasOne;
use HolySword\Database\Model\Relations\HasMany;
use HolySword\Database\Model\Relations\BelongsTo;
use HolySword\Database\Model\Relations\BelongsToMany;

/**
 * 模型关联关系 Trait
 * 
 * @package HolySword\Database\Model\Traits
 */
trait HasRelationships
{
    /**
     * 已加载的关联
     * 
     * @var array
     */
    protected array $relations = [];

    /**
     * 需要预加载的关联
     * 
     * @var array
     */
    protected array $with = [];

    /**
     * 定义一对一关联
     * 
     * @param string $related 关联模型类名
     * @param string|null $foreignKey 外键（默认：当前模型名_id）
     * @param string|null $localKey 本地键（默认：id）
     * @return HasOne
     * 
     * @example
     * public function profile(): HasOne
     * {
     *     return $this->hasOne(Profile::class);
     * }
     */
    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        $instance = new $related();
        
        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey = $localKey ?? $this->getKeyName();

        return new HasOne($instance, $this, $foreignKey, $localKey);
    }

    /**
     * 定义一对多关联
     * 
     * @param string $related 关联模型类名
     * @param string|null $foreignKey 外键
     * @param string|null $localKey 本地键
     * @return HasMany
     * 
     * @example
     * public function orders(): HasMany
     * {
     *     return $this->hasMany(Order::class);
     * }
     */
    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        $instance = new $related();
        
        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey = $localKey ?? $this->getKeyName();

        return new HasMany($instance, $this, $foreignKey, $localKey);
    }

    /**
     * 定义反向一对多关联
     * 
     * @param string $related 关联模型类名
     * @param string|null $foreignKey 外键
     * @param string|null $ownerKey 关联模型的主键
     * @return BelongsTo
     * 
     * @example
     * public function user(): BelongsTo
     * {
     *     return $this->belongsTo(User::class);
     * }
     */
    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        $instance = new $related();
        
        // 默认外键是关联模型名称 + _id
        $foreignKey = $foreignKey ?? $instance->getForeignKey();
        $ownerKey = $ownerKey ?? $instance->getKeyName();

        return new BelongsTo($instance, $this, $foreignKey, $ownerKey);
    }

    /**
     * 定义多对多关联
     * 
     * @param string $related 关联模型类名
     * @param string|null $table 中间表名
     * @param string|null $foreignPivotKey 当前模型在中间表的外键
     * @param string|null $relatedPivotKey 关联模型在中间表的外键
     * @param string|null $parentKey 当前模型的主键
     * @param string|null $relatedKey 关联模型的主键
     * @return BelongsToMany
     * 
     * @example
     * public function roles(): BelongsToMany
     * {
     *     return $this->belongsToMany(Role::class, 'user_roles');
     * }
     */
    public function belongsToMany(
        string $related,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        ?string $parentKey = null,
        ?string $relatedKey = null
    ): BelongsToMany {
        $instance = new $related();
        
        // 默认中间表名：两个表名按字母排序后用下划线连接
        if ($table === null) {
            $tables = [$this->getTable(), $instance->getTable()];
            sort($tables);
            $table = implode('_', $tables);
        }

        $foreignPivotKey = $foreignPivotKey ?? $this->getForeignKey();
        $relatedPivotKey = $relatedPivotKey ?? $instance->getForeignKey();
        $parentKey = $parentKey ?? $this->getKeyName();
        $relatedKey = $relatedKey ?? $instance->getKeyName();

        return new BelongsToMany(
            $instance,
            $this,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey
        );
    }

    /**
     * 获取外键名称
     * 
     * @return string
     */
    public function getForeignKey(): string
    {
        return $this->snakeCase(class_basename($this)) . '_id';
    }

    /**
     * 获取关联
     * 
     * @param string $key 关联名称
     * @return mixed
     */
    public function getRelation(string $key): mixed
    {
        return $this->relations[$key] ?? null;
    }

    /**
     * 设置关联
     * 
     * @param string $key 关联名称
     * @param mixed $value 关联值
     * @return static
     */
    public function setRelation(string $key, mixed $value): static
    {
        $this->relations[$key] = $value;
        return $this;
    }

    /**
     * 检查关联是否已加载
     * 
     * @param string $key 关联名称
     * @return bool
     */
    public function relationLoaded(string $key): bool
    {
        return array_key_exists($key, $this->relations);
    }

    /**
     * 获取所有已加载的关联
     * 
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * 获取关联值（懒加载）
     * 
     * @param string $key 关联名称
     * @return mixed
     */
    public function getRelationValue(string $key): mixed
    {
        // 如果已加载，直接返回
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        // 检查是否定义了关联方法
        if (!method_exists($this, $key)) {
            return null;
        }

        // 懒加载关联
        $relation = $this->$key();
        
        if (!is_object($relation)) {
            return null;
        }

        // 获取关联结果并缓存
        $results = $relation->getResults();
        $this->setRelation($key, $results);

        return $results;
    }

    /**
     * 设置预加载的关联
     * 
     * @param array|string $relations 关联名称
     * @return static
     */
    public function setWith(array|string $relations): static
    {
        $this->with = is_array($relations) ? $relations : func_get_args();
        return $this;
    }

    /**
     * 获取预加载的关联
     * 
     * @return array
     */
    public function getWith(): array
    {
        return $this->with;
    }

    /**
     * 加载关联
     * 
     * @param array|string $relations 关联名称
     * @return static
     */
    public function load(array|string $relations): static
    {
        $relations = is_array($relations) ? $relations : func_get_args();

        foreach ($relations as $relation => $constraints) {
            // 支持简单的关联名和带约束的关联
            if (is_numeric($relation)) {
                $relation = $constraints;
                $constraints = null;
            }

            // 解析嵌套关联
            if (str_contains($relation, '.')) {
                $this->loadNested($relation, $constraints);
            } else {
                $this->loadRelation($relation, $constraints);
            }
        }

        return $this;
    }

    /**
     * 加载单个关联
     * 
     * @param string $relation 关联名称
     * @param callable|null $constraints 约束回调
     * @return void
     */
    protected function loadRelation(string $relation, ?callable $constraints = null): void
    {
        if (!method_exists($this, $relation)) {
            return;
        }

        $relationInstance = $this->$relation();

        if ($constraints !== null) {
            $constraints($relationInstance);
        }

        $results = $relationInstance->getResults();
        $this->setRelation($relation, $results);
    }

    /**
     * 加载嵌套关联
     * 
     * @param string $relation 嵌套关联名（如 orders.items）
     * @param callable|null $constraints 约束回调
     * @return void
     */
    protected function loadNested(string $relation, ?callable $constraints = null): void
    {
        $parts = explode('.', $relation);
        $first = array_shift($parts);
        $nested = implode('.', $parts);

        // 先加载第一层关联
        $this->loadRelation($first);

        // 递归加载嵌套关联
        $related = $this->getRelation($first);

        if ($related !== null) {
            if (is_array($related) || $related instanceof \Traversable) {
                foreach ($related as $model) {
                    if (method_exists($model, 'load')) {
                        $model->load($nested);
                    }
                }
            } elseif (method_exists($related, 'load')) {
                $related->load($nested);
            }
        }
    }

    /**
     * 将字符串转换为 snake_case
     * 
     * @param string $value 值
     * @return string
     */
    protected function snakeCase(string $value): string
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $value));
        }

        return $value;
    }

    /**
     * 获取类的短名称
     * 
     * @param object|string $class 类名或对象
     * @return string
     */
    protected function classBasename(object|string $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}
