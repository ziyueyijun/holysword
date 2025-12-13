<?php

/**
 * HolySword Framework - CORS 跨域中间件
 * 
 * 处理跨域资源共享请求，自动处理预检请求并添加必要的响应头。
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
 * CORS 跨域中间件
 * 
 * 处理跨域资源共享（Cross-Origin Resource Sharing）请求。
 * 自动处理预检请求（OPTIONS）并添加必要的响应头。
 * 
 * 使用示例:
 * ```php
 * // 在路由中使用
 * $router->aliasMiddleware('cors', CorsMiddleware::class);
 * $router->get('/api/data', [Controller::class, 'index'])->middleware('cors');
 * 
 * // 或在路由分组中使用
 * $router->group(['prefix' => 'api', 'middleware' => 'cors'], function($router) {
 *     // API 路由
 * });
 * ```
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * 允许的来源域名
     * '*' 表示允许所有域名
     */
    protected array $allowedOrigins = ['*'];

    /**
     * 允许的 HTTP 方法
     */
    protected array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    /**
     * 允许的请求头
     */
    protected array $allowedHeaders = [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
        'X-CSRF-Token',
    ];

    /**
     * 暴露给前端的响应头
     */
    protected array $exposedHeaders = [];

    /**
     * 预检请求缓存时间（秒）
     */
    protected int $maxAge = 86400;

    /**
     * 是否允许携带凭证（Cookie）
     */
    protected bool $supportsCredentials = true;

    /**
     * 处理请求
     * 
     * 处理预检请求或为实际请求添加 CORS 头
     * 
     * @param Request $request HTTP 请求对象
     * @param Closure $next 下一个中间件
     * @return Response HTTP 响应
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 处理预检请求
        if ($request->method() === 'OPTIONS') {
            return $this->handlePreflightRequest($request);
        }

        // 处理实际请求
        $response = $next($request);

        return $this->addCorsHeaders($request, $response);
    }

    /**
     * 处理预检请求
     * 
     * @param Request $request HTTP 请求对象
     * @return Response 204 空响应
     */
    protected function handlePreflightRequest(Request $request): Response
    {
        $response = Response::make('', 204);
        return $this->addCorsHeaders($request, $response);
    }

    /**
     * 添加 CORS 响应头
     * 
     * @param Request $request HTTP 请求对象
     * @param Response $response HTTP 响应对象
     * @return Response 添加 CORS 头后的响应
     */
    protected function addCorsHeaders(Request $request, Response $response): Response
    {
        $origin = $request->header('origin', '*');

        // 检查是否允许该来源
        if ($this->isOriginAllowed($origin)) {
            $response->header('Access-Control-Allow-Origin', $origin);
        }

        // 允许的方法
        $response->header(
            'Access-Control-Allow-Methods',
            implode(', ', $this->allowedMethods)
        );

        // 允许的请求头
        $response->header(
            'Access-Control-Allow-Headers',
            implode(', ', $this->allowedHeaders)
        );

        // 暴露的响应头
        if (!empty($this->exposedHeaders)) {
            $response->header(
                'Access-Control-Expose-Headers',
                implode(', ', $this->exposedHeaders)
            );
        }

        // 预检请求缓存时间
        $response->header('Access-Control-Max-Age', (string) $this->maxAge);

        // 是否允许携带凭证
        if ($this->supportsCredentials) {
            $response->header('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    /**
     * 检查来源是否被允许
     * 
     * @param string $origin 请求来源
     * @return bool 是否允许
     */
    protected function isOriginAllowed(string $origin): bool
    {
        if (in_array('*', $this->allowedOrigins)) {
            return true;
        }

        return in_array($origin, $this->allowedOrigins);
    }

    /**
     * 设置允许的来源
     * 
     * @param array $origins 允许的域名列表
     * @return self
     */
    public function setAllowedOrigins(array $origins): self
    {
        $this->allowedOrigins = $origins;
        return $this;
    }

    /**
     * 设置允许的方法
     * 
     * @param array $methods 允许的 HTTP 方法列表
     * @return self
     */
    public function setAllowedMethods(array $methods): self
    {
        $this->allowedMethods = $methods;
        return $this;
    }

    /**
     * 设置允许的请求头
     * 
     * @param array $headers 允许的请求头列表
     * @return self
     */
    public function setAllowedHeaders(array $headers): self
    {
        $this->allowedHeaders = $headers;
        return $this;
    }
}
