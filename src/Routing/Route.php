<?php

/**
 * HolySword Framework - 路由实例
 * 
 * @package    HolySword
 * @subpackage Routing
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Routing;

use Closure;

/**
 * 路由实例类
 * 
 * 表示单个路由的所有属性，包括 HTTP 方法、URI、处理器、中间件等。
 * 支持链式调用设置各种属性。
 * 
 * 使用示例:
 * ```php
 * $router->get('/users/{id}', [UserController::class, 'show'])
 *     ->name('users.show')
 *     ->middleware('auth');
 * ```
 * 
 * @package HolySword\Routing
 */
class Route
{
    /**
     * 支持的 HTTP 方法列表
     * 
     * @var array<string>
     */
    protected array $methods;

    /**
     * 路由 URI 模式
     * 
     * 支持参数占位符，如 /users/{id}
     * 
     * @var string
     */
    protected string $uri;

    /**
     * 路由处理器
     * 
     * 可以是闭包、控制器数组 [Controller::class, 'method'] 或字符串 'Controller@method'
     * 
     * @var Closure|array|string
     */
    protected Closure|array|string $action;

    /**
     * 从 URL 中提取的路由参数
     * 
     * @var array<string, string>
     */
    protected array $parameters = [];

    /**
     * 路由名称，用于生成 URL
     * 
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * 路由中间件列表
     * 
     * @var array<string>
     */
    protected array $middleware = [];

    /**
     * 控制器命名空间前缀
     * 
     * @var string|null
     */
    protected ?string $namespace = null;

    /**
     * 创建路由实例
     * 
     * @param array $methods 支持的 HTTP 方法
     * @param string $uri 路由 URI
     * @param Closure|array|string $action 路由处理器
     */
    public function __construct(array $methods, string $uri, Closure|array|string $action)
    {
        $this->methods = $methods;
        $this->uri = '/' . trim($uri, '/');
        $this->action = $action;
    }

    /**
     * 设置路由名称
     * 
     * 命名路由可用于生成 URL
     * 
     * @param string $name 路由名称
     * @return static 支持链式调用
     * 
     * @example
     * ```php
     * $router->get('/users/{id}', ...)->name('users.show');
     * ```
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 添加中间件到路由
     * 
     * @param string|array $middleware 中间件名称或数组
     * @return static 支持链式调用
     * 
     * @example
     * ```php
     * $router->get('/admin', ...)->middleware('auth');
     * $router->get('/admin', ...)->middleware(['auth', 'admin']);
     * ```
     */
    public function middleware(string|array $middleware): static
    {
        $this->middleware = array_merge(
            $this->middleware,
            is_array($middleware) ? $middleware : [$middleware]
        );

        return $this;
    }

    /**
     * 设置控制器命名空间
     * 
     * @param string $namespace 命名空间前缀
     * @return static 支持链式调用
     */
    public function namespace(string $namespace): static
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * 设置路由参数（由路由器调用）
     * 
     * @param array $parameters 从 URL 中提取的参数
     * @return static
     */
    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * 获取所有路由参数
     * 
     * @return array 参数数组
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * 获取指定路由参数值
     * 
     * @param string $name 参数名
     * @param mixed $default 默认值
     * @return mixed 参数值
     */
    public function parameter(string $name, mixed $default = null): mixed
    {
        return $this->parameters[$name] ?? $default;
    }

    /**
     * 获取支持的 HTTP 方法列表
     * 
     * @return array HTTP 方法数组
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * 获取路由 URI
     * 
     * @return string URI 模式
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * 获取路由处理器
     * 
     * 如果设置了命名空间，会自动添加前缀
     * 
     * @return Closure|array|string 处理器
     */
    public function getAction(): Closure|array|string
    {
        if ($this->namespace && is_string($this->action)) {
            return $this->namespace . '\\' . $this->action;
        }

        if ($this->namespace && is_array($this->action)) {
            // 创建副本避免修改原始 action，防止多次调用重复添加命名空间
            $action = $this->action;
            $action[0] = $this->namespace . '\\' . $action[0];
            return $action;
        }

        return $this->action;
    }

    /**
     * 获取路由名称
     * 
     * @return string|null 路由名称或 null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * 获取路由中间件列表
     * 
     * @return array 中间件数组
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }
}
