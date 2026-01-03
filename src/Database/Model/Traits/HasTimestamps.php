<?php

/**
 * HolySword Framework - 时间戳处理 Trait
 * 
 * 自动维护 created_at 和 updated_at 时间戳字段。
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
 * 时间戳处理 Trait
 * 
 * @package HolySword\Database\Model\Traits
 */
trait HasTimestamps
{
    /**
     * 是否自动维护时间戳
     * 
     * @var bool
     */
    public bool $timestamps = true;

    /**
     * 创建时间字段名
     * 
     * @var string|null
     */
    protected ?string $createdAtColumn = 'created_at';

    /**
     * 更新时间字段名
     * 
     * @var string|null
     */
    protected ?string $updatedAtColumn = 'updated_at';

    /**
     * 检查是否使用时间戳
     * 
     * @return bool
     */
    public function usesTimestamps(): bool
    {
        return $this->timestamps;
    }

    /**
     * 获取创建时间字段名
     * 
     * @return string|null
     */
    public function getCreatedAtColumn(): ?string
    {
        return $this->createdAtColumn;
    }

    /**
     * 获取更新时间字段名
     * 
     * @return string|null
     */
    public function getUpdatedAtColumn(): ?string
    {
        return $this->updatedAtColumn;
    }

    /**
     * 设置创建时间字段名
     * 
     * @param string|null $column 字段名
     * @return static
     */
    public function setCreatedAtColumn(?string $column): static
    {
        $this->createdAtColumn = $column;
        return $this;
    }

    /**
     * 设置更新时间字段名
     * 
     * @param string|null $column 字段名
     * @return static
     */
    public function setUpdatedAtColumn(?string $column): static
    {
        $this->updatedAtColumn = $column;
        return $this;
    }

    /**
     * 设置创建时间
     * 
     * @return void
     */
    protected function setCreatedAt(): void
    {
        if (!$this->usesTimestamps() || $this->createdAtColumn === null) {
            return;
        }

        $this->setAttribute($this->createdAtColumn, $this->freshTimestamp());
    }

    /**
     * 设置更新时间
     * 
     * @return void
     */
    protected function setUpdatedAt(): void
    {
        if (!$this->usesTimestamps() || $this->updatedAtColumn === null) {
            return;
        }

        $this->setAttribute($this->updatedAtColumn, $this->freshTimestamp());
    }

    /**
     * 获取当前时间戳
     * 
     * @return string
     */
    public function freshTimestamp(): string
    {
        return date($this->getDateFormat());
    }

    /**
     * 获取日期格式
     * 
     * @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat ?? 'Y-m-d H:i:s';
    }

    /**
     * 设置日期格式
     * 
     * @param string $format 日期格式
     * @return static
     */
    public function setDateFormat(string $format): static
    {
        $this->dateFormat = $format;
        return $this;
    }

    /**
     * 触摸时间戳（仅更新 updated_at）
     * 
     * @return bool
     */
    public function touch(): bool
    {
        if (!$this->usesTimestamps()) {
            return false;
        }

        $this->setUpdatedAt();

        return $this->save();
    }

    /**
     * 禁用时间戳更新
     * 
     * @return static
     */
    public function withoutTimestamps(): static
    {
        $this->timestamps = false;
        return $this;
    }

    /**
     * 静态方法：在回调中禁用时间戳
     * 
     * @param callable $callback 回调函数
     * @return mixed
     */
    public static function withoutTimestampsOn(callable $callback): mixed
    {
        $model = new static();
        $originalValue = $model->timestamps;
        
        $model->timestamps = false;
        
        try {
            return $callback($model);
        } finally {
            $model->timestamps = $originalValue;
        }
    }
}
