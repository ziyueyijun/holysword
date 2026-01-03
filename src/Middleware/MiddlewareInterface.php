<?php

/**
 * HolySword Framework - 中间件接口
 * 
 * 该文件定义了中间件的标准接口。
 * 所有自定义中间件都必须实现此接口，以确保可以被路由系统正确处理。
 * 
 * 中间件用于在请求到达控制器之前或响应发送给客户端之后执行操作，
 * 常见用途包括：
 * - 用户认证和授权
 * - 请求日志记录
 * - CORS 跨域处理
 * - 请求频率限制
 * - 响应压缩和缓存
 * 
 * @package    HolySword
 * @subpackage Middleware
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Middleware;

use Closure;
use HolySword\Http\Request;
use HolySword\Http\Response;

/**
 * 中间件接口
 * 
 * 定义中间件必须实现的方法签名。
 * 中间件使用管道模式处理请求，每个中间件可以：
 * - 在请求到达处理器之前执行前置逻辑
 * - 决定是否继续传递请求到下一个中间件/处理器
 * - 在响应返回客户端之前执行后置逻辑
 * - 直接返回响应（短路请求）
 * 
 * 使用示例:
 * ```php
 * class AuthMiddleware implements MiddlewareInterface
 * {
 *     public function handle(Request $request, Closure $next): Response
 *     {
 *         // 前置逻辑：检查用户是否登录
 *         if (!$this->isAuthenticated($request)) {
 *             return Response::error('未授权', 401, 401);
 *         }
 * 
 *         // 继续处理请求
 *         $response = $next($request);
 * 
 *         // 后置逻辑：可以修改响应
 *         return $response->header('X-Custom-Header', 'value');
 *     }
 * }
 * ```
 * 
 * 注册中间件:
 * ```php
 * // 在路由中使用
 * $router->get('/admin', [AdminController::class, 'index'])
 *     ->middleware(AuthMiddleware::class);
 * 
 * // 使用别名
 * $router->aliasMiddleware('auth', AuthMiddleware::class);
 * $router->get('/admin', [AdminController::class, 'index'])
 *     ->middleware('auth');
 * ```
 * 
 * @package HolySword\Middleware
 */
interface MiddlewareInterface
{
    /**
     * 处理传入的 HTTP 请求
     * 
     * 中间件的核心方法，负责处理请求并返回响应。
     * 可以选择将请求传递给下一个中间件，或者直接返回响应。
     * 
     * @param Request $request HTTP 请求对象，包含请求的所有信息
     * @param Closure $next 下一个中间件或最终处理器的闭包，调用 $next($request) 继续管道
     * @return Response HTTP 响应对象
     * 
     * @example
     * ```php
     * public function handle(Request $request, Closure $next): Response
     * {
     *     // 前置处理
     *     $start = microtime(true);
     * 
     *     // 调用下一个中间件/处理器
     *     $response = $next($request);
     * 
     *     // 后置处理
     *     $duration = microtime(true) - $start;
     *     Log::info("请求耗时: {$duration}s");
     * 
     *     return $response;
     * }
     * ```
     */
    public function handle(Request $request, Closure $next): Response;
}
