<?php

/**
 * HolySword Framework - 模型属性处理 Trait
 * 
 * 提供模型属性的访问、修改、转换和隐藏功能。
 * 支持访问器、修改器和属性类型转换。
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

/**
 * 模型属性处理 Trait
 * 
 * 功能包括：
 * - 属性访问器（getXxxAttribute）
 * - 属性修改器（setXxxAttribute）
 * - 属性类型转换（$casts）
 * - 属性隐藏（$hidden）
 * - 批量赋值保护（$fillable, $guarded）
 * - 脏数据检测
 * 
 * @package HolySword\Database\Model\Traits
 */
trait HasAttributes
{
    /**
     * 模型的原始属性
     * 
     * @var array
     */
    protected array $attributes = [];

    /**
     * 模型的原始值（用于脏数据检测）
     * 
     * @var array
     */
    protected array $original = [];

    /**
     * 已改变的属性
     * 
     * @var array
     */
    protected array $changes = [];

    /**
     * 属性类型转换
     * 
     * 支持的类型：int, integer, float, double, real, string, bool, boolean,
     * array, json, object, date, datetime, timestamp
     * 
     * @var array
     */
    protected array $casts = [];

    /**
     * 可批量赋值的属性
     * 
     * @var array
     */
    protected array $fillable = [];

    /**
     * 不可批量赋值的属性
     * 
     * @var array
     */
    protected array $guarded = ['*'];

    /**
     * 序列化时隐藏的属性
     * 
     * @var array
     */
    protected array $hidden = [];

    /**
     * 序列化时可见的属性
     * 
     * @var array
     */
    protected array $visible = [];

    /**
     * 追加到序列化结果的属性
     * 
     * @var array
     */
    protected array $appends = [];

    /**
     * 日期格式
     * 
     * @var string
     */
    protected string $dateFormat = 'Y-m-d H:i:s';

    /**
     * 获取属性值
     * 
     * @param string $key 属性名
     * @return mixed
     */
    public function getAttribute(string $key): mixed
    {
        if (!$key) {
            return null;
        }

        // 先检查是否有访问器
        $value = $this->getAttributeFromArray($key);
        
        // 调用访问器
        if ($this->hasGetMutator($key)) {
            return $this->mutateAttribute($key, $value);
        }

        // 类型转换
        if ($this->hasCast($key)) {
            return $this->castAttribute($key, $value);
        }

        return $value;
    }

    /**
     * 从属性数组获取值
     * 
     * @param string $key 属性名
     * @return mixed
     */
    protected function getAttributeFromArray(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * 设置属性值
     * 
     * @param string $key 属性名
     * @param mixed $value 属性值
     * @return static
     */
    public function setAttribute(string $key, mixed $value): static
    {
        // 调用修改器
        if ($this->hasSetMutator($key)) {
            $this->setMutatedAttributeValue($key, $value);
            return $this;
        }

        // 类型转换
        if ($this->hasCast($key)) {
            $value = $this->castAttributeForSet($key, $value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * 检查是否存在访问器
     * 
     * @param string $key 属性名
     * @return bool
     */
    protected function hasGetMutator(string $key): bool
    {
        return method_exists($this, 'get' . $this->studly($key) . 'Attribute');
    }

    /**
     * 检查是否存在修改器
     * 
     * @param string $key 属性名
     * @return bool
     */
    protected function hasSetMutator(string $key): bool
    {
        return method_exists($this, 'set' . $this->studly($key) . 'Attribute');
    }

    /**
     * 通过访问器获取属性
     * 
     * @param string $key 属性名
     * @param mixed $value 原始值
     * @return mixed
     */
    protected function mutateAttribute(string $key, mixed $value): mixed
    {
        return $this->{'get' . $this->studly($key) . 'Attribute'}($value);
    }

    /**
     * 通过修改器设置属性
     * 
     * @param string $key 属性名
     * @param mixed $value 新值
     * @return void
     */
    protected function setMutatedAttributeValue(string $key, mixed $value): void
    {
        $this->{'set' . $this->studly($key) . 'Attribute'}($value);
    }

    /**
     * 检查属性是否需要类型转换
     * 
     * @param string $key 属性名
     * @return bool
     */
    protected function hasCast(string $key): bool
    {
        return array_key_exists($key, $this->getCasts());
    }

    /**
     * 获取类型转换配置
     * 
     * @return array
     */
    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     * 转换属性类型（读取时）
     * 
     * @param string $key 属性名
     * @param mixed $value 原始值
     * @return mixed
     */
    protected function castAttribute(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $castType = $this->getCastType($key);

        return match ($castType) {
            'int', 'integer' => (int) $value,
            'real', 'float', 'double' => (float) $value,
            'decimal' => $this->asDecimal($value, explode(':', $this->getCasts()[$key])[1] ?? 2),
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'object' => $this->fromJson($value, true),
            'array', 'json' => $this->fromJson($value),
            'date' => $this->asDate($value),
            'datetime', 'timestamp' => $this->asDateTime($value),
            default => $value,
        };
    }

    /**
     * 转换属性类型（写入时）
     * 
     * @param string $key 属性名
     * @param mixed $value 新值
     * @return mixed
     */
    protected function castAttributeForSet(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $castType = $this->getCastType($key);

        return match ($castType) {
            'int', 'integer' => (int) $value,
            'real', 'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'object', 'array', 'json' => $this->asJson($value),
            'date', 'datetime', 'timestamp' => $this->formatDateForStorage($value),
            default => $value,
        };
    }

    /**
     * 获取转换类型
     * 
     * @param string $key 属性名
     * @return string
     */
    protected function getCastType(string $key): string
    {
        $cast = $this->getCasts()[$key] ?? '';
        
        // 处理 decimal:2 这种格式
        if (str_contains($cast, ':')) {
            return explode(':', $cast)[0];
        }

        return $cast;
    }

    /**
     * 转换为 JSON 字符串
     * 
     * @param mixed $value 值
     * @return string
     */
    protected function asJson(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 从 JSON 字符串解析
     * 
     * @param string $value JSON 字符串
     * @param bool $asObject 是否返回对象
     * @return mixed
     */
    protected function fromJson(string $value, bool $asObject = false): mixed
    {
        return json_decode($value, !$asObject);
    }

    /**
     * 转换为 Decimal
     * 
     * @param mixed $value 值
     * @param int $decimals 小数位数
     * @return string
     */
    protected function asDecimal(mixed $value, int $decimals): string
    {
        return number_format((float) $value, $decimals, '.', '');
    }

    /**
     * 转换为日期
     * 
     * @param mixed $value 值
     * @return string
     */
    protected function asDate(mixed $value): string
    {
        return date('Y-m-d', strtotime($value));
    }

    /**
     * 转换为日期时间
     * 
     * @param mixed $value 值
     * @return string
     */
    protected function asDateTime(mixed $value): string
    {
        return date($this->dateFormat, strtotime($value));
    }

    /**
     * 格式化日期用于存储
     * 
     * @param mixed $value 值
     * @return string
     */
    protected function formatDateForStorage(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format($this->dateFormat);
        }

        return date($this->dateFormat, strtotime($value));
    }

    /**
     * 批量填充属性
     * 
     * @param array $attributes 属性数组
     * @return static
     */
    public function fill(array $attributes): static
    {
        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * 强制填充属性（跳过 fillable 检查）
     * 
     * @param array $attributes 属性数组
     * @return static
     */
    public function forceFill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * 过滤可填充的属性
     * 
     * @param array $attributes 属性数组
     * @return array
     */
    protected function fillableFromArray(array $attributes): array
    {
        if (!empty($this->fillable) && !$this->isGuarded('*')) {
            return array_intersect_key($attributes, array_flip($this->fillable));
        }

        return $attributes;
    }

    /**
     * 检查属性是否可填充
     * 
     * @param string $key 属性名
     * @return bool
     */
    public function isFillable(string $key): bool
    {
        // 如果 fillable 数组不为空，检查是否在其中
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable);
        }

        // 检查是否被保护
        if ($this->isGuarded($key)) {
            return false;
        }

        return true;
    }

    /**
     * 检查属性是否被保护
     * 
     * @param string $key 属性名
     * @return bool
     */
    public function isGuarded(string $key): bool
    {
        if (empty($this->guarded)) {
            return false;
        }

        return in_array($key, $this->guarded) || $this->guarded === ['*'];
    }

    /**
     * 同步原始属性
     * 
     * @return static
     */
    public function syncOriginal(): static
    {
        $this->original = $this->attributes;
        $this->changes = [];

        return $this;
    }

    /**
     * 检查属性是否改变
     * 
     * @param string|array|null $attributes 属性名
     * @return bool
     */
    public function isDirty(string|array|null $attributes = null): bool
    {
        return $this->hasChanges(
            $this->getDirty(),
            is_array($attributes) ? $attributes : func_get_args()
        );
    }

    /**
     * 检查属性是否未改变
     * 
     * @param string|array|null $attributes 属性名
     * @return bool
     */
    public function isClean(string|array|null $attributes = null): bool
    {
        return !$this->isDirty(...func_get_args());
    }

    /**
     * 获取改变的属性
     * 
     * @return array
     */
    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * 检查是否有变化
     * 
     * @param array $changes 变化的属性
     * @param array $attributes 要检查的属性
     * @return bool
     */
    protected function hasChanges(array $changes, array $attributes): bool
    {
        if (empty($attributes)) {
            return count($changes) > 0;
        }

        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $changes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取原始值
     * 
     * @param string|null $key 属性名
     * @return mixed
     */
    public function getOriginal(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->original;
        }

        return $this->original[$key] ?? null;
    }

    /**
     * 转换为数组
     * 
     * @return array
     */
    public function toArray(): array
    {
        $attributes = [];

        foreach ($this->getArrayableAttributes() as $key => $value) {
            $attributes[$key] = $this->getAttribute($key);
        }

        // 添加追加的属性
        foreach ($this->appends as $key) {
            $attributes[$key] = $this->getAttribute($key);
        }

        return $attributes;
    }

    /**
     * 获取可转换为数组的属性
     * 
     * @return array
     */
    protected function getArrayableAttributes(): array
    {
        if (!empty($this->visible)) {
            $attributes = array_intersect_key($this->attributes, array_flip($this->visible));
        } else {
            $attributes = $this->attributes;
        }

        // 移除隐藏的属性
        foreach ($this->hidden as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }

    /**
     * 转换为 JSON
     * 
     * @param int $options JSON 选项
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取只包含指定键的属性数组
     * 
     * @param array $keys 要包含的键
     * @return array
     */
    public function only(array $keys): array
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = $this->getAttribute($key);
        }
        
        return $result;
    }

    /**
     * 获取排除指定键的属性数组
     * 
     * @param array $keys 要排除的键
     * @return array
     */
    public function except(array $keys): array
    {
        $result = $this->toArray();
        
        foreach ($keys as $key) {
            unset($result[$key]);
        }
        
        return $result;
    }

    /**
     * 设置隐藏属性
     * 
     * @param array $hidden 隐藏的属性
     * @return static
     */
    public function setHidden(array $hidden): static
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * 设置可见属性
     * 
     * @param array $visible 可见的属性
     * @return static
     */
    public function setVisible(array $visible): static
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * 临时显示隐藏的属性
     * 
     * @param array $attributes 属性名
     * @return static
     */
    public function makeVisible(array $attributes): static
    {
        $this->hidden = array_diff($this->hidden, $attributes);
        return $this;
    }

    /**
     * 临时隐藏属性
     * 
     * @param array $attributes 属性名
     * @return static
     */
    public function makeHidden(array $attributes): static
    {
        $this->hidden = array_merge($this->hidden, $attributes);
        return $this;
    }

    /**
     * 获取所有属性
     * 
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * 设置所有属性
     * 
     * @param array $attributes 属性数组
     * @return static
     */
    public function setRawAttributes(array $attributes): static
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * 将 snake_case 转换为 StudlyCase
     * 
     * @param string $value 值
     * @return string
     */
    protected function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $value)));
    }
}
