<?php

/**
 * HolySword Framework - 模型基类
 * 
 * 提供 ORM 的核心功能，是所有模型的基类。
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

use HolySword\Database\Model\Traits\HasAttributes;
use HolySword\Database\Model\Traits\HasTimestamps;
use HolySword\Database\Model\Traits\HasEvents;
use HolySword\Database\Model\Traits\HasRelationships;

/**
 * 模型基类
 * 
 * 使用示例:
 * ```php
 * class User extends Model
 * {
 *     protected string $table = 'users';
 *     protected array $fillable = ['name', 'email'];
 *     protected array $hidden = ['password'];
 * }
 * 
 * // 查询
 * $user = User::find(1);
 * $users = User::where('status', 1)->get();
 * 
 * // 创建
 * $user = User::create(['name' => '张三', 'email' => 'test@example.com']);
 * 
 * // 更新
 * $user->name = '李四';
 * $user->save();
 * 
 * // 删除
 * $user->delete();
 * ```
 * 
 * @package HolySword\Database\Model
 */
abstract class Model implements \ArrayAccess, \JsonSerializable
{
    use HasAttributes;
    use HasTimestamps;
    use HasEvents;
    use HasRelationships;

    /**
     * 表名
     * 
     * @var string
     */
    protected string $table = '';

    /**
     * 主键名
     * 
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * 主键类型
     * 
     * @var string
     */
    protected string $keyType = 'int';

    /**
     * 是否自增主键
     * 
     * @var bool
     */
    public bool $incrementing = true;

    /**
     * 模型是否存在于数据库
     * 
     * @var bool
     */
    public bool $exists = false;

    /**
     * 连接名称
     * 
     * @var string|null
     */
    protected ?string $connection = null;

    /**
     * 默认每页数量
     * 
     * @var int
     */
    protected int $perPage = 15;

    /**
     * 全局作用域
     * 
     * @var array
     */
    protected static array $globalScopes = [];

    /**
     * 创建模型实例
     * 
     * @param array $attributes 初始属性
     */
    public function __construct(array $attributes = [])
    {
        $this->initializeEvents();
        $this->syncOriginal();
        
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }

    /**
     * 创建新模型实例
     * 
     * @param array $attributes 属性
     * @return static
     */
    public function newInstance(array $attributes = []): static
    {
        $model = new static($attributes);
        $model->exists = false;

        return $model;
    }

    /**
     * 从数据库数据创建模型实例
     * 
     * @param array $attributes 属性
     * @param bool $exists 是否已存在
     * @return static
     */
    public function newFromBuilder(array $attributes = [], bool $exists = true): static
    {
        $model = $this->newInstance();
        $model->setRawAttributes($attributes);
        $model->exists = $exists;
        $model->syncOriginal();

        return $model;
    }

    /**
     * 获取表名
     * 
     * @return string
     */
    public function getTable(): string
    {
        if (!empty($this->table)) {
            return $this->table;
        }

        // 自动推断表名：UserProfile -> user_profiles
        $className = class_basename($this);
        return $this->pluralize($this->snakeCase($className));
    }

    /**
     * 设置表名
     * 
     * @param string $table 表名
     * @return static
     */
    public function setTable(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * 获取主键名
     * 
     * @return string
     */
    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    /**
     * 获取主键值
     * 
     * @return mixed
     */
    public function getKey(): mixed
    {
        return $this->getAttribute($this->getKeyName());
    }

    /**
     * 设置主键值
     * 
     * @param mixed $value 主键值
     * @return static
     */
    public function setKey(mixed $value): static
    {
        $this->setAttribute($this->getKeyName(), $value);
        return $this;
    }

    /**
     * 获取主键类型
     * 
     * @return string
     */
    public function getKeyType(): string
    {
        return $this->keyType;
    }

    /**
     * 获取新的查询构建器
     * 
     * @return Builder
     */
    public function newQuery(): Builder
    {
        return $this->newModelQuery()->withGlobalScopes();
    }

    /**
     * 获取不带全局作用域的查询构建器
     * 
     * @return Builder
     */
    public function newModelQuery(): Builder
    {
        return new Builder($this);
    }

    /**
     * 获取查询构建器（静态方法）
     * 
     * @return Builder
     */
    public static function query(): Builder
    {
        return (new static())->newQuery();
    }

    /**
     * 保存模型
     * 
     * @return bool
     */
    public function save(): bool
    {
        $query = $this->newModelQuery();

        // 触发 saving 事件
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        // 判断是更新还是创建
        if ($this->exists) {
            $saved = $this->isDirty() ? $this->performUpdate($query) : true;
        } else {
            $saved = $this->performInsert($query);
        }

        if ($saved) {
            // 触发 saved 事件
            $this->fireModelEvent('saved');
            $this->syncOriginal();
        }

        return $saved;
    }

    /**
     * 执行插入操作
     * 
     * @param Builder $query 查询构建器
     * @return bool
     */
    protected function performInsert(Builder $query): bool
    {
        // 触发 creating 事件
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        // 设置时间戳
        if ($this->usesTimestamps()) {
            $this->setCreatedAt();
            $this->setUpdatedAt();
        }

        $attributes = $this->getAttributes();

        // 移除主键（如果是自增）
        if ($this->incrementing) {
            unset($attributes[$this->getKeyName()]);
        }

        // 执行插入
        $id = $query->insert($attributes);

        // 设置主键
        if ($this->incrementing && $id) {
            $this->setAttribute($this->getKeyName(), $id);
        }

        $this->exists = true;

        // 触发 created 事件
        $this->fireModelEvent('created');

        return true;
    }

    /**
     * 执行更新操作
     * 
     * @param Builder $query 查询构建器
     * @return bool
     */
    protected function performUpdate(Builder $query): bool
    {
        // 触发 updating 事件
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        // 设置更新时间
        if ($this->usesTimestamps()) {
            $this->setUpdatedAt();
        }

        $dirty = $this->getDirty();

        if (!empty($dirty)) {
            $query->where($this->getKeyName(), $this->getKey())
                  ->update($dirty);
        }

        // 触发 updated 事件
        $this->fireModelEvent('updated');

        return true;
    }

    /**
     * 更新模型属性
     * 
     * @param array $attributes 属性
     * @return bool
     */
    public function update(array $attributes = []): bool
    {
        if (!$this->exists) {
            return false;
        }

        return $this->fill($attributes)->save();
    }

    /**
     * 删除模型
     * 
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        // 触发 deleting 事件
        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        $this->performDelete();

        // 触发 deleted 事件
        $this->fireModelEvent('deleted');

        $this->exists = false;

        return true;
    }

    /**
     * 执行删除操作
     * 
     * @return void
     */
    protected function performDelete(): void
    {
        $this->newModelQuery()
             ->where($this->getKeyName(), $this->getKey())
             ->delete();
    }

    /**
     * 刷新模型数据
     * 
     * @return static|null
     */
    public function refresh(): ?static
    {
        if (!$this->exists) {
            return null;
        }

        $fresh = $this->newQuery()
                      ->where($this->getKeyName(), $this->getKey())
                      ->first();

        if ($fresh) {
            $this->setRawAttributes($fresh->getAttributes());
            $this->syncOriginal();
        }

        return $this;
    }

    /**
     * 获取新的模型实例
     * 
     * @return static|null
     */
    public function fresh(): ?static
    {
        if (!$this->exists) {
            return null;
        }

        return $this->newQuery()
                    ->where($this->getKeyName(), $this->getKey())
                    ->first();
    }

    /**
     * 复制模型
     * 
     * @param array|null $except 排除的属性
     * @return static
     */
    public function replicate(?array $except = null): static
    {
        $except = $except ?? [
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];

        $attributes = array_diff_key($this->getAttributes(), array_flip($except));

        return $this->newInstance($attributes);
    }

    // ==================== 静态便捷方法 ====================

    /**
     * 根据主键查找
     * 
     * @param mixed $id 主键值
     * @return static|null
     */
    public static function find(mixed $id): ?static
    {
        return static::query()->find($id);
    }

    /**
     * 根据主键查找或抛出异常
     * 
     * @param mixed $id 主键值
     * @return static
     * @throws \RuntimeException
     */
    public static function findOrFail(mixed $id): static
    {
        $model = static::find($id);

        if ($model === null) {
            throw new \RuntimeException(
                sprintf('模型 [%s] 未找到记录 [%s]', static::class, $id)
            );
        }

        return $model;
    }

    /**
     * 查找或创建
     * 
     * @param array $attributes 查询条件
     * @param array $values 创建时的额外值
     * @return static
     */
    public static function firstOrCreate(array $attributes, array $values = []): static
    {
        $model = static::where($attributes)->first();

        if ($model !== null) {
            return $model;
        }

        return static::create(array_merge($attributes, $values));
    }

    /**
     * 查找或新建实例
     * 
     * @param array $attributes 查询条件
     * @param array $values 新建时的额外值
     * @return static
     */
    public static function firstOrNew(array $attributes, array $values = []): static
    {
        $model = static::where($attributes)->first();

        if ($model !== null) {
            return $model;
        }

        return new static(array_merge($attributes, $values));
    }

    /**
     * 更新或创建
     * 
     * @param array $attributes 查询条件
     * @param array $values 更新/创建的值
     * @return static
     */
    public static function updateOrCreate(array $attributes, array $values = []): static
    {
        $model = static::where($attributes)->first();

        if ($model !== null) {
            $model->fill($values)->save();
            return $model;
        }

        return static::create(array_merge($attributes, $values));
    }

    /**
     * 创建模型
     * 
     * @param array $attributes 属性
     * @return static
     */
    public static function create(array $attributes = []): static
    {
        $model = new static($attributes);
        $model->save();

        return $model;
    }

    /**
     * 销毁模型
     * 
     * @param mixed $ids 主键值（单个或数组）
     * @return int 删除的数量
     */
    public static function destroy(mixed $ids): int
    {
        $ids = is_array($ids) ? $ids : func_get_args();

        if (empty($ids)) {
            return 0;
        }

        $count = 0;

        foreach ($ids as $id) {
            $model = static::find($id);
            if ($model && $model->delete()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * 获取所有记录
     * 
     * @return Collection
     */
    public static function all(): Collection
    {
        return static::query()->get();
    }

    // ==================== 魔术方法 ====================

    /**
     * 动态获取属性
     * 
     * @param string $key 属性名
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key) ?? $this->getRelationValue($key);
    }

    /**
     * 动态设置属性
     * 
     * @param string $key 属性名
     * @param mixed $value 属性值
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * 检查属性是否存在
     * 
     * @param string $key 属性名
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return $this->getAttribute($key) !== null || $this->relationLoaded($key);
    }

    /**
     * 删除属性
     * 
     * @param string $key 属性名
     * @return void
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key], $this->relations[$key]);
    }

    /**
     * 动态调用查询方法
     * 
     * @param string $method 方法名
     * @param array $parameters 参数
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        // 调用本地作用域
        if (method_exists($this, 'scope' . ucfirst($method))) {
            return $this->callScope($method, $parameters);
        }

        return $this->newQuery()->$method(...$parameters);
    }

    /**
     * 静态调用查询方法
     * 
     * @param string $method 方法名
     * @param array $parameters 参数
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        return (new static())->$method(...$parameters);
    }

    /**
     * 调用本地作用域
     * 
     * @param string $scope 作用域名
     * @param array $parameters 参数
     * @return Builder
     */
    protected function callScope(string $scope, array $parameters): Builder
    {
        $query = $this->newQuery();
        
        array_unshift($parameters, $query);
        
        $this->{'scope' . ucfirst($scope)}(...$parameters);

        return $query;
    }

    // ==================== 接口实现 ====================

    /**
     * ArrayAccess: 检查偏移量是否存在
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->getAttribute($offset) !== null;
    }

    /**
     * ArrayAccess: 获取偏移量的值
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttribute($offset);
    }

    /**
     * ArrayAccess: 设置偏移量的值
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * ArrayAccess: 删除偏移量
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * JsonSerializable: 返回可序列化的数据
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 转换为字符串
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    // ==================== 辅助方法 ====================

    /**
     * 单词复数化（简单实现）
     * 
     * @param string $word 单词
     * @return string
     */
    protected function pluralize(string $word): string
    {
        $lastChar = substr($word, -1);
        $lastTwoChars = substr($word, -2);

        if ($lastChar === 'y' && !in_array($lastTwoChars, ['ay', 'ey', 'iy', 'oy', 'uy'])) {
            return substr($word, 0, -1) . 'ies';
        }

        if (in_array($lastChar, ['s', 'x', 'z']) || in_array($lastTwoChars, ['sh', 'ch'])) {
            return $word . 'es';
        }

        return $word . 's';
    }

    /**
     * 获取类的短名称
     * 
     * @param object|string $class 类
     * @return string
     */
    protected function classBasename(object|string $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

    /**
     * 注册全局作用域
     * 
     * @param string $identifier 标识符
     * @param object|callable $scope 作用域
     * @return void
     */
    public static function addGlobalScope(string $identifier, object|callable $scope): void
    {
        self::$globalScopes[static::class][$identifier] = $scope;
    }

    /**
     * 获取全局作用域
     * 
     * @return array
     */
    public function getGlobalScopes(): array
    {
        return self::$globalScopes[static::class] ?? [];
    }
}
