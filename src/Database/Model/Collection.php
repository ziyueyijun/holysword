<?php

/**
 * HolySword Framework - 模型集合类
 * 
 * 提供模型实例的集合操作功能，类似于 Laravel Collection。
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

use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use ArrayIterator;
use Traversable;

/**
 * 模型集合类
 * 
 * @package HolySword\Database\Model
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * 集合中的元素
     * 
     * @var array
     */
    protected array $items = [];

    /**
     * 创建集合实例
     * 
     * @param array $items 初始元素
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * 创建新的集合实例
     * 
     * @param array $items 元素
     * @return static
     */
    public static function make(array $items = []): static
    {
        return new static($items);
    }

    /**
     * 获取所有元素
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * 获取元素数量
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * 检查集合是否为空
     * 
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * 检查集合是否不为空
     * 
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * 获取第一个元素
     * 
     * @param callable|null $callback 过滤回调
     * @param mixed $default 默认值
     * @return mixed
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $this->items[0] ?? $default;
        }

        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * 获取最后一个元素
     * 
     * @param callable|null $callback 过滤回调
     * @param mixed $default 默认值
     * @return mixed
     */
    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $this->items ? end($this->items) : $default;
        }

        $items = array_reverse($this->items, true);

        foreach ($items as $key => $item) {
            if ($callback($item, $key)) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * 获取指定键的元素
     * 
     * @param mixed $key 键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(mixed $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    /**
     * 添加元素
     * 
     * @param mixed $item 元素
     * @return static
     */
    public function push(mixed $item): static
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * 在集合前面添加元素
     * 
     * @param mixed $item 元素
     * @return static
     */
    public function prepend(mixed $item): static
    {
        array_unshift($this->items, $item);
        return $this;
    }

    /**
     * 设置指定键的值
     * 
     * @param mixed $key 键
     * @param mixed $value 值
     * @return static
     */
    public function put(mixed $key, mixed $value): static
    {
        $this->items[$key] = $value;
        return $this;
    }

    /**
     * 移除并返回最后一个元素
     * 
     * @return mixed
     */
    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    /**
     * 移除并返回第一个元素
     * 
     * @return mixed
     */
    public function shift(): mixed
    {
        return array_shift($this->items);
    }

    /**
     * 遍历集合
     * 
     * @param callable $callback 回调函数
     * @return static
     */
    public function each(callable $callback): static
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * 映射集合
     * 
     * @param callable $callback 回调函数
     * @return static
     */
    public function map(callable $callback): static
    {
        $result = [];

        foreach ($this->items as $key => $item) {
            $result[$key] = $callback($item, $key);
        }

        return new static($result);
    }

    /**
     * 过滤集合
     * 
     * @param callable|null $callback 过滤回调
     * @return static
     */
    public function filter(?callable $callback = null): static
    {
        if ($callback === null) {
            return new static(array_filter($this->items));
        }

        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * 拒绝满足条件的元素
     * 
     * @param callable $callback 回调函数
     * @return static
     */
    public function reject(callable $callback): static
    {
        return $this->filter(function ($item, $key) use ($callback) {
            return !$callback($item, $key);
        });
    }

    /**
     * 根据条件筛选
     * 
     * @param string $key 键
     * @param mixed $operator 操作符或值
     * @param mixed $value 值
     * @return static
     */
    public function where(string $key, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->filter(function ($item) use ($key, $operator, $value) {
            $itemValue = $this->dataGet($item, $key);

            return match ($operator) {
                '=' => $itemValue == $value,
                '===' => $itemValue === $value,
                '!=' => $itemValue != $value,
                '!==' => $itemValue !== $value,
                '>' => $itemValue > $value,
                '>=' => $itemValue >= $value,
                '<' => $itemValue < $value,
                '<=' => $itemValue <= $value,
                default => $itemValue == $value,
            };
        });
    }

    /**
     * 筛选指定键值不为空的元素
     * 
     * @param string $key 键
     * @return static
     */
    public function whereNotNull(string $key): static
    {
        return $this->filter(function ($item) use ($key) {
            return $this->dataGet($item, $key) !== null;
        });
    }

    /**
     * 筛选指定键值为空的元素
     * 
     * @param string $key 键
     * @return static
     */
    public function whereNull(string $key): static
    {
        return $this->filter(function ($item) use ($key) {
            return $this->dataGet($item, $key) === null;
        });
    }

    /**
     * 筛选指定键值在数组中的元素
     * 
     * @param string $key 键
     * @param array $values 值数组
     * @return static
     */
    public function whereIn(string $key, array $values): static
    {
        return $this->filter(function ($item) use ($key, $values) {
            return in_array($this->dataGet($item, $key), $values, true);
        });
    }

    /**
     * 筛选指定键值不在数组中的元素
     * 
     * @param string $key 键
     * @param array $values 值数组
     * @return static
     */
    public function whereNotIn(string $key, array $values): static
    {
        return $this->filter(function ($item) use ($key, $values) {
            return !in_array($this->dataGet($item, $key), $values, true);
        });
    }

    /**
     * 提取指定键的值
     * 
     * @param string $key 键
     * @param string|null $keyBy 使用指定键作为新数组的键
     * @return static
     */
    public function pluck(string $key, ?string $keyBy = null): static
    {
        $result = [];

        foreach ($this->items as $item) {
            $value = $this->dataGet($item, $key);

            if ($keyBy !== null) {
                $itemKey = $this->dataGet($item, $keyBy);
                $result[$itemKey] = $value;
            } else {
                $result[] = $value;
            }
        }

        return new static($result);
    }

    /**
     * 获取唯一值
     * 
     * @param string|callable|null $key 键或回调
     * @return static
     */
    public function unique(string|callable|null $key = null): static
    {
        if ($key === null) {
            return new static(array_unique($this->items, SORT_REGULAR));
        }

        $exists = [];
        $result = [];

        foreach ($this->items as $item) {
            $value = is_callable($key) ? $key($item) : $this->dataGet($item, $key);

            if (!in_array($value, $exists, true)) {
                $exists[] = $value;
                $result[] = $item;
            }
        }

        return new static($result);
    }

    /**
     * 排序
     * 
     * @param string|callable|null $key 键或回调
     * @param string $direction 排序方向 asc/desc
     * @return static
     */
    public function sortBy(string|callable|null $key = null, string $direction = 'asc'): static
    {
        $items = $this->items;

        usort($items, function ($a, $b) use ($key, $direction) {
            $valueA = $key === null ? $a : (is_callable($key) ? $key($a) : $this->dataGet($a, $key));
            $valueB = $key === null ? $b : (is_callable($key) ? $key($b) : $this->dataGet($b, $key));

            $result = $valueA <=> $valueB;

            return $direction === 'desc' ? -$result : $result;
        });

        return new static($items);
    }

    /**
     * 降序排序
     * 
     * @param string|callable|null $key 键或回调
     * @return static
     */
    public function sortByDesc(string|callable|null $key = null): static
    {
        return $this->sortBy($key, 'desc');
    }

    /**
     * 反转集合
     * 
     * @return static
     */
    public function reverse(): static
    {
        return new static(array_reverse($this->items, true));
    }

    /**
     * 重新索引集合
     * 
     * @return static
     */
    public function values(): static
    {
        return new static(array_values($this->items));
    }

    /**
     * 获取所有键
     * 
     * @return static
     */
    public function keys(): static
    {
        return new static(array_keys($this->items));
    }

    /**
     * 合并集合
     * 
     * @param iterable $items 要合并的元素
     * @return static
     */
    public function merge(iterable $items): static
    {
        $items = $items instanceof Collection ? $items->all() : (array) $items;
        
        return new static(array_merge($this->items, $items));
    }

    /**
     * 连接集合
     * 
     * @param iterable $items 要连接的元素
     * @return static
     */
    public function concat(iterable $items): static
    {
        $result = $this->items;

        foreach ($items as $item) {
            $result[] = $item;
        }

        return new static($result);
    }

    /**
     * 截取集合
     * 
     * @param int $offset 偏移量
     * @param int|null $length 长度
     * @return static
     */
    public function slice(int $offset, ?int $length = null): static
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * 分块
     * 
     * @param int $size 每块大小
     * @return static
     */
    public function chunk(int $size): static
    {
        if ($size <= 0) {
            return new static([]);
        }

        $chunks = [];

        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    /**
     * 扁平化集合
     * 
     * @param int $depth 深度
     * @return static
     */
    public function flatten(int $depth = INF): static
    {
        return new static($this->flattenArray($this->items, $depth));
    }

    /**
     * 递归扁平化数组
     * 
     * @param array $array 数组
     * @param int $depth 深度
     * @return array
     */
    protected function flattenArray(array $array, int $depth): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item) && !$item instanceof Collection) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $values = $item instanceof Collection ? $item->all() : $item;
                $result = array_merge($result, array_values($values));
            } else {
                $values = $item instanceof Collection ? $item->all() : $item;
                $result = array_merge($result, $this->flattenArray($values, $depth - 1));
            }
        }

        return $result;
    }

    /**
     * 分组
     * 
     * @param string|callable $key 分组键或回调
     * @return static
     */
    public function groupBy(string|callable $key): static
    {
        $result = [];

        foreach ($this->items as $item) {
            $groupKey = is_callable($key) ? $key($item) : $this->dataGet($item, $key);
            
            if (!isset($result[$groupKey])) {
                $result[$groupKey] = new static([]);
            }

            $result[$groupKey]->push($item);
        }

        return new static($result);
    }

    /**
     * 以指定键为索引
     * 
     * @param string|callable $key 键或回调
     * @return static
     */
    public function keyBy(string|callable $key): static
    {
        $result = [];

        foreach ($this->items as $item) {
            $itemKey = is_callable($key) ? $key($item) : $this->dataGet($item, $key);
            $result[$itemKey] = $item;
        }

        return new static($result);
    }

    /**
     * 检查是否包含元素
     * 
     * @param mixed $key 键或值
     * @param mixed $operator 操作符或值
     * @param mixed $value 值
     * @return bool
     */
    public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1) {
            if (is_callable($key)) {
                foreach ($this->items as $item) {
                    if ($key($item)) {
                        return true;
                    }
                }
                return false;
            }

            return in_array($key, $this->items, true);
        }

        return $this->where(...func_get_args())->isNotEmpty();
    }

    /**
     * 归约集合
     * 
     * @param callable $callback 回调函数
     * @param mixed $initial 初始值
     * @return mixed
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * 求和
     * 
     * @param string|callable|null $key 键或回调
     * @return int|float
     */
    public function sum(string|callable|null $key = null): int|float
    {
        if ($key === null) {
            return array_sum($this->items);
        }

        return $this->reduce(function ($carry, $item) use ($key) {
            return $carry + (is_callable($key) ? $key($item) : $this->dataGet($item, $key));
        }, 0);
    }

    /**
     * 求平均值
     * 
     * @param string|callable|null $key 键或回调
     * @return int|float|null
     */
    public function avg(string|callable|null $key = null): int|float|null
    {
        $count = $this->count();

        if ($count === 0) {
            return null;
        }

        return $this->sum($key) / $count;
    }

    /**
     * 求最大值
     * 
     * @param string|callable|null $key 键或回调
     * @return mixed
     */
    public function max(string|callable|null $key = null): mixed
    {
        if ($key === null) {
            return max($this->items);
        }

        $max = null;
        $maxValue = null;

        foreach ($this->items as $item) {
            $value = is_callable($key) ? $key($item) : $this->dataGet($item, $key);

            if ($maxValue === null || $value > $maxValue) {
                $max = $item;
                $maxValue = $value;
            }
        }

        return $max;
    }

    /**
     * 求最小值
     * 
     * @param string|callable|null $key 键或回调
     * @return mixed
     */
    public function min(string|callable|null $key = null): mixed
    {
        if ($key === null) {
            return min($this->items);
        }

        $min = null;
        $minValue = null;

        foreach ($this->items as $item) {
            $value = is_callable($key) ? $key($item) : $this->dataGet($item, $key);

            if ($minValue === null || $value < $minValue) {
                $min = $item;
                $minValue = $value;
            }
        }

        return $min;
    }

    /**
     * 转换为数组
     * 
     * @return array
     */
    public function toArray(): array
    {
        return array_map(function ($item) {
            if ($item instanceof Model) {
                return $item->toArray();
            }
            if ($item instanceof Collection) {
                return $item->toArray();
            }
            if (is_object($item) && method_exists($item, 'toArray')) {
                return $item->toArray();
            }
            return $item;
        }, $this->items);
    }

    /**
     * 转换为 JSON
     * 
     * @param int $options JSON 选项
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * JSON 序列化
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 获取迭代器
     * 
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * 检查偏移量是否存在
     * 
     * @param mixed $offset 偏移量
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * 获取偏移量的值
     * 
     * @param mixed $offset 偏移量
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    /**
     * 设置偏移量的值
     * 
     * @param mixed $offset 偏移量
     * @param mixed $value 值
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * 删除偏移量
     * 
     * @param mixed $offset 偏移量
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * 从数据中获取值（支持点号分隔的嵌套键）
     * 
     * @param mixed $target 目标数据
     * @param string $key 键
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function dataGet(mixed $target, string $key, mixed $default = null): mixed
    {
        if ($target === null) {
            return $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($target)) {
                if (!array_key_exists($segment, $target)) {
                    return $default;
                }
                $target = $target[$segment];
            } elseif ($target instanceof ArrayAccess) {
                if (!$target->offsetExists($segment)) {
                    return $default;
                }
                $target = $target[$segment];
            } elseif (is_object($target)) {
                if (method_exists($target, 'getAttribute')) {
                    $target = $target->getAttribute($segment);
                } elseif (property_exists($target, $segment)) {
                    $target = $target->$segment;
                } else {
                    return $default;
                }
            } else {
                return $default;
            }
        }

        return $target;
    }

    /**
     * 调试信息
     * 
     * @return array
     */
    public function __debugInfo(): array
    {
        return $this->items;
    }

    /**
     * 转换为字符串
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * 加载关联（模型集合专用）
     * 
     * @param array|string $relations 关联名称
     * @return static
     */
    public function load(array|string $relations): static
    {
        $relations = is_array($relations) ? $relations : func_get_args();

        foreach ($this->items as $item) {
            if ($item instanceof Model) {
                $item->load($relations);
            }
        }

        return $this;
    }

    /**
     * 获取模型主键数组
     * 
     * @return array
     */
    public function modelKeys(): array
    {
        return $this->map(function ($model) {
            return $model instanceof Model ? $model->getKey() : null;
        })->filter()->all();
    }

    /**
     * 通过主键查找模型
     * 
     * @param mixed $key 主键
     * @param mixed $default 默认值
     * @return Model|static|null
     */
    public function find(mixed $key, mixed $default = null): mixed
    {
        if (is_array($key)) {
            return $this->filter(function ($model) use ($key) {
                return $model instanceof Model && in_array($model->getKey(), $key);
            });
        }

        return $this->first(function ($model) use ($key) {
            return $model instanceof Model && $model->getKey() == $key;
        }, $default);
    }
}
