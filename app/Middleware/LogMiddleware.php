<?php

/**
 * HolySword Framework - 日志中间件
 * 
 * 记录请求信息和响应时间，演示中间件的使用方式。
 * 
 * @package    App
 * @subpackage Middleware
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace App\Middleware;

use Closure;
use HolySword\Http\Request;
use HolySword\Http\Response;
use HolySword\Middleware\MiddlewareInterface;

/**
 * 日志中间件
 * 
 * 记录每个请求的方法、路径和响应时间
 * 
 * 使用示例:
 * ```php
 * // 在路由中使用
 * $router->aliasMiddleware('log', LogMiddleware::class);
 * $router->get('/api/users', [Controller::class, 'index'])->middleware('log');
 * 
 * // 响应头中将包含 X-Response-Time
 * ```
 * 
 * @package App\Middleware
 */
class LogMiddleware implements MiddlewareInterface
{
    /**
     * 处理请求
     * 
     * 记录请求开始时间，执行下一个中间件，然后计算响应时间
     * 
     * @param Request $request HTTP 请求对象
     * @param Closure $next 下一个中间件
     * @return Response HTTP 响应（带 X-Response-Time 头）
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 前置处理：记录请求开始时间
        $startTime = microtime(true);
        
        // 调用下一个中间件或处理器
        $response = $next($request);
        
        // 后置处理：计算响应时间
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        // 添加响应时间头
        return $response->header('X-Response-Time', $duration . 'ms');
    }
}
