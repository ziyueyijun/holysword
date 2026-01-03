<?php

/**
 * HolySword Framework - 软删除作用域
 * 
 * 自动过滤已软删除的记录，并提供查询已删除记录的方法。
 * 
 * @package    HolySword
 * @subpackage Database\Model\Scopes
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Database\Model\Scopes;

use HolySword\Database\Model\Builder;
use HolySword\Database\Model\Model;

/**
 * 软删除作用域
 * 
 * @package HolySword\Database\Model\Scopes
 */
class SoftDeletingScope implements Scope
{
    /**
     * 所有应用的扩展方法
     * 
     * @var array
     */
    protected array $extensions = [
        'Restore',
        'RestoreOrCreate',
        'CreateOrRestore',
        'WithTrashed',
        'WithoutTrashed',
        'OnlyTrashed',
    ];

    /**
     * 应用作用域到查询构建器
     * 
     * @param Builder $builder 查询构建器
     * @param Model $model 模型实例
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNull($model->getQualifiedDeletedAtColumn());
    }

    /**
     * 扩展查询构建器
     * 
     * @param Builder $builder 查询构建器
     * @return void
     */
    public function extend(Builder $builder): void
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }

        // 监听删除事件，实现软删除
        $builder->onDelete(function (Builder $builder) {
            $column = $this->getDeletedAtColumn($builder);
            return $builder->update([
                $column => $builder->getModel()->freshTimestampString(),
            ]);
        });
    }

    /**
     * 获取 deleted_at 列名
     * 
     * @param Builder $builder 查询构建器
     * @return string
     */
    protected function getDeletedAtColumn(Builder $builder): string
    {
        if (count($builder->getQuery()->joins ?? []) > 0) {
            return $builder->getModel()->getQualifiedDeletedAtColumn();
        }

        return $builder->getModel()->getDeletedAtColumn();
    }

    /**
     * 添加恢复方法
     * 
     * @param Builder $builder 查询构建器
     * @return void
     */
    protected function addRestore(Builder $builder): void
    {
        $builder->macro('restore', function (Builder $builder) {
            $builder->withTrashed();

            return $builder->update([
                $builder->getModel()->getDeletedAtColumn() => null,
            ]);
        });
    }

    /**
     * 添加 restoreOrCreate 方法
     * 
     * @param Builder $builder 查询构建器
     * @return void
     */
    protected function addRestoreOrCreate(Builder $builder): void
    {
        $builder->macro('restoreOrCreate', function (Builder $builder, array $attributes = [], array $values = []) {
            $builder->withTrashed();

            $instance = $builder->firstOrNew($attributes, $values);

            if ($instance->trashed()) {
                $instance->restore();
            }

            return $instance;
        });
    }

    /**
     * 添加 createOrRestore 方法
     * 
     * @param Builder $builder 查询构建器
     * @return void
     */
    protected function addCreateOrRestore(Builder $builder): void
    {
        $builder->macro('createOrRestore', function (Builder $builder, array $attributes = [], array $values = []) {
            $builder->withTrashed();

            $instance = $builder->firstOrCreate($attributes, $values);

            if ($instance->trashed()) {
                $instance->restore();
            }

            return $instance;
        });
    }

    /**
     * 添加 withTrashed 方法（包含软删除的记录）
     * 
     * @param Builder $builder 查询构建器
     * @return void
     */
    protected function addWithTrashed(Builder $builder): void
    {
        $builder->macro('withTrashed', function (Builder $builder, bool $withTrashed = true) {
            if (!$withTrashed) {
                return $builder->withoutTrashed();
            }

            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * 添加 withoutTrashed 方法（不包含软删除的记录）
     * 
     * @param Builder $builder 查询构建器
     * @return void
     */
    protected function addWithoutTrashed(Builder $builder): void
    {
        $builder->macro('withoutTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)
                ->whereNull($model->getQualifiedDeletedAtColumn());

            return $builder;
        });
    }

    /**
     * 添加 onlyTrashed 方法（只查询软删除的记录）
     * 
     * @param Builder $builder 查询构建器
     * @return void
     */
    protected function addOnlyTrashed(Builder $builder): void
    {
        $builder->macro('onlyTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)
                ->whereNotNull($model->getQualifiedDeletedAtColumn());

            return $builder;
        });
    }
}
