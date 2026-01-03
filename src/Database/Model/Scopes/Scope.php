<?php

/**
 * HolySword Framework - 作用域接口
 * 
 * 定义全局作用域必须实现的接口。
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
 * 作用域接口
 * 
 * 全局作用域用于自动应用查询约束条件。
 * 
 * @package HolySword\Database\Model\Scopes
 */
interface Scope
{
    /**
     * 应用作用域到查询构建器
     * 
     * @param Builder $builder 查询构建器
     * @param Model $model 模型实例
     * @return void
     */
    public function apply(Builder $builder, Model $model): void;
}
