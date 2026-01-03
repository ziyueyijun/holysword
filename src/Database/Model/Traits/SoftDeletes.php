<?php

/**
 * HolySword Framework - 软删除 Trait
 * 
 * 提供模型的软删除功能，使用 deleted_at 字段标记删除状态。
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

use HolySword\Database\Model\Scopes\SoftDeletingScope;
use DateTime;

/**
 * 软删除 Trait
 * 
 * 使用此 Trait 的模型将支持软删除功能。
 * 
 * @package HolySword\Database\Model\Traits
 */
trait SoftDeletes
{
    /**
     * 是否正在进行强制删除
     * 
     * @var bool
     */
    protected bool $forceDeleting = false;

    /**
     * 启动软删除 Trait
     * 
     * @return void
     */
    public static function bootSoftDeletes(): void
    {
        static::addGlobalScope(new SoftDeletingScope());
    }

    /**
     * 初始化软删除 Trait
     * 
     * @return void
     */
    public function initializeSoftDeletes(): void
    {
        // 确保 deleted_at 在日期属性中
        if (!in_array($this->getDeletedAtColumn(), $this->dates ?? [])) {
            $this->dates[] = $this->getDeletedAtColumn();
        }
    }

    /**
     * 强制删除模型（真实删除）
     * 
     * @return bool
     */
    public function forceDelete(): bool
    {
        $this->forceDeleting = true;

        $deleted = $this->performDelete();

        $this->forceDeleting = false;

        return $deleted;
    }

    /**
     * 执行删除操作
     * 
     * @return bool
     */
    protected function performDelete(): bool
    {
        // 如果是强制删除，使用父类的删除
        if ($this->forceDeleting) {
            return $this->performForceDelete();
        }

        // 软删除：设置 deleted_at 字段
        return $this->runSoftDelete();
    }

    /**
     * 执行强制删除
     * 
     * @return bool
     */
    protected function performForceDelete(): bool
    {
        // 触发强制删除事件
        if ($this->fireModelEvent('forceDeleting') === false) {
            return false;
        }

        $query = $this->newQueryWithoutScopes();
        $result = $query->where($this->getKeyName(), $this->getKey())->delete() > 0;

        if ($result) {
            $this->exists = false;
            $this->fireModelEvent('forceDeleted', false);
        }

        return $result;
    }

    /**
     * 执行软删除
     * 
     * @return bool
     */
    protected function runSoftDelete(): bool
    {
        // 触发删除事件
        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        $time = $this->freshTimestamp();
        $column = $this->getDeletedAtColumn();

        // 设置 deleted_at
        $this->setAttribute($column, $time);

        // 更新数据库
        $query = $this->newQueryWithoutScopes();
        $result = $query->where($this->getKeyName(), $this->getKey())
            ->update([$column => $this->formatTimestamp($time)]) > 0;

        if ($result) {
            $this->fireModelEvent('deleted', false);
        }

        // 同步原始属性
        $this->syncOriginalAttribute($column);

        return $result;
    }

    /**
     * 恢复软删除的模型
     * 
     * @return bool
     */
    public function restore(): bool
    {
        // 触发恢复事件
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $column = $this->getDeletedAtColumn();

        // 清除 deleted_at
        $this->setAttribute($column, null);

        // 更新数据库
        $query = $this->newQueryWithoutScopes();
        $result = $query->where($this->getKeyName(), $this->getKey())
            ->update([$column => null]) > 0;

        if ($result) {
            $this->exists = true;
            $this->fireModelEvent('restored', false);
        }

        // 同步原始属性
        $this->syncOriginalAttribute($column);

        return $result;
    }

    /**
     * 检查模型是否被软删除
     * 
     * @return bool
     */
    public function trashed(): bool
    {
        return $this->getAttribute($this->getDeletedAtColumn()) !== null;
    }

    /**
     * 检查是否正在强制删除
     * 
     * @return bool
     */
    public function isForceDeleting(): bool
    {
        return $this->forceDeleting;
    }

    /**
     * 获取删除时间列名
     * 
     * @return string
     */
    public function getDeletedAtColumn(): string
    {
        return defined(static::class . '::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }

    /**
     * 获取完整的删除时间列名（包含表名）
     * 
     * @return string
     */
    public function getQualifiedDeletedAtColumn(): string
    {
        return $this->getTable() . '.' . $this->getDeletedAtColumn();
    }

    /**
     * 获取当前时间戳
     * 
     * @return DateTime
     */
    public function freshTimestamp(): DateTime
    {
        return new DateTime();
    }

    /**
     * 格式化时间戳
     * 
     * @param DateTime $timestamp 时间戳
     * @return string
     */
    protected function formatTimestamp(DateTime $timestamp): string
    {
        return $timestamp->format($this->getDateFormat());
    }

    /**
     * 获取日期格式
     * 
     * @return string
     */
    protected function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * 创建不带全局作用域的查询
     * 
     * @return \HolySword\Database\Model\Builder
     */
    abstract public function newQueryWithoutScopes(): \HolySword\Database\Model\Builder;

    /**
     * 触发模型事件
     * 
     * @param string $event 事件名称
     * @param bool $halt 是否中断
     * @return mixed
     */
    abstract protected function fireModelEvent(string $event, bool $halt = true): mixed;

    /**
     * 同步单个原始属性
     * 
     * @param string $attribute 属性名
     * @return static
     */
    abstract public function syncOriginalAttribute(string $attribute): static;

    /**
     * 注册恢复事件回调
     * 
     * @param callable $callback 回调函数
     * @return void
     */
    public static function restoring(callable $callback): void
    {
        static::registerModelEvent('restoring', $callback);
    }

    /**
     * 注册已恢复事件回调
     * 
     * @param callable $callback 回调函数
     * @return void
     */
    public static function restored(callable $callback): void
    {
        static::registerModelEvent('restored', $callback);
    }

    /**
     * 注册强制删除中事件回调
     * 
     * @param callable $callback 回调函数
     * @return void
     */
    public static function forceDeleting(callable $callback): void
    {
        static::registerModelEvent('forceDeleting', $callback);
    }

    /**
     * 注册已强制删除事件回调
     * 
     * @param callable $callback 回调函数
     * @return void
     */
    public static function forceDeleted(callable $callback): void
    {
        static::registerModelEvent('forceDeleted', $callback);
    }
}
