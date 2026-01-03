<?php

/**
 * HolySword Framework - 路由系统
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
use HolySword\Container\Container;
use HolySword\Http\Request;
use HolySword\Http\Response;

/**
 * 路由器类
 * 
 * 负责路由的注册、匹配和分发。
 * 支持多种 HTTP 方法、路由参数、路由分组和中间件。
 * 
 * 使用示例:
 * ```php
 * $router = app('router');
 * 
 * // 基础路由
 * $router->get('/', function() { return 'Hello'; });
 * $router->post('/users', [UserController::class, 'store']);
 * 
 * // 带参数的路由
 * $router->get('/users/{id}', [UserController::class, 'show']);
 * 
 * // 路由分组
 * $router->group(['prefix' => 'api'], function($router) {
 *     $router->get('/users', [UserController::class, 'index']);
 * });
 * ```
 * 
 * @package HolySword\Routing
 */
class Router
{
    /**
     * 依赖注入容器实例
     * 
     * 用于解析控制器和中间件
     * 
     * @var Container
     */
    protected Container $container;

    /**
     * 已注册的路由列表
     * 
     * 按 HTTP 方法分组存储: ['GET' => ['/path' => Route], ...]
     * 
     * @var array<string, array<string, Route>>
     */
    protected array $routes = [];

    /**
     * 路由分组属性栈
     * 
     * 用于支持嵌套的路由分组
     * 
     * @var array
     */
    protected array $groupStack = [];

    /**
     * 中间件别名映射表
     * 
     * 映射中间件别名到完整类名
     * 
     * @var array<string, string>
     */
    protected array $middleware = [];

    /**
     * 命名路由映射表
     * 
     * @var array<string, Route>
     */
    protected array $namedRoutes = [];

    /**
     * 创建路由器实例
     * 
     * @param Container $container 依赖注入容器
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * 注册 GET 路由
     * 
     * 同时注册 HEAD 方法以支持预检请求
     * 
     * @param string $uri 路由 URI
     * @param Closure|array|string $action 路由处理器（闭包、控制器数组或字符串）
     * @return Route 路由实例，可链式调用
     * 
     * @example
     * ```php
     * $router->get('/users', function() { return 'Users'; });
     * $router->get('/users/{id}', [UserController::class, 'show']);
     * ```
     */
    public function get(string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * 注册 POST 路由
     * 
     * @param string $uri 路由 URI
     * @param Closure|array|string $action 路由处理器
     * @return Route 路由实例
     * 
     * @example
     * ```php
     * $router->post('/users', [UserController::class, 'store']);
     * ```
     */
    public function post(string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    /**
     * 注册 PUT 路由
     * 
     * @param string $uri 路由 URI
     * @param Closure|array|string $action 路由处理器
     * @return Route 路由实例
     */
    public function put(string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    /**
     * 注册 PATCH 路由
     * 
     * @param string $uri 路由 URI
     * @param Closure|array|string $action 路由处理器
     * @return Route 路由实例
     */
    public function patch(string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    /**
     * 注册 DELETE 路由
     * 
     * @param string $uri 路由 URI
     * @param Closure|array|string $action 路由处理器
     * @return Route 路由实例
     */
    public function delete(string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    /**
     * 注册 OPTIONS 路由
     * 
     * 用于处理 CORS 预检请求
     * 
     * @param string $uri 路由 URI
     * @param Closure|array|string $action 路由处理器
     * @return Route 路由实例
     */
    public function options(string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(['OPTIONS'], $uri, $action);
    }

    /**
     * 注册支持所有 HTTP 方法的路由
     * 
     * @param string $uri 路由 URI
     * @param Closure|array|string $action 路由处理器
     * @return Route 路由实例
     * 
     * @example
     * ```php
     * $router->any('/webhook', [WebhookController::class, 'handle']);
     * ```
     */
    public function any(string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }

    /**
     * 注册支持指定 HTTP 方法的路由
     * 
     * @param array $methods HTTP 方法数组 ['GET', 'POST']
     * @param string $uri 路由 URI
     * @param Closure|array|string $action 路由处理器
     * @return Route 路由实例
     * 
     * @example
     * ```php
     * $router->match(['get', 'post'], '/form', [FormController::class, 'handle']);
     * ```
     */
    public function match(array $methods, string $uri, Closure|array|string $action): Route
    {
        return $this->addRoute(array_map('strtoupper', $methods), $uri, $action);
    }

    /**
     * 内部方法：添加路由到路由表
     * 
     * @param array $methods HTTP 方法数组
     * @param string $uri 路由 URI
     * @param Closure|array|string $action 路由处理器
     * @return Route 路由实例
     */
    protected function addRoute(array $methods, string $uri, Closure|array|string $action): Route
    {
        $uri = $this->prefixUri($uri);
        
        $route = new Route($methods, $uri, $action);

        // 应用路由组属性
        if (!empty($this->groupStack)) {
            $this->mergeGroupAttributes($route);
        }

        foreach ($methods as $method) {
            $this->routes[$method][$uri] = $route;
        }

        return $route;
    }

    /**
     * 创建路由分组
     * 
     * 将共享相同属性的路由分组在一起，支持前缀、中间件和命名空间
     * 
     * @param array $attributes 分组属性 ['prefix' => '', 'middleware' => [], 'namespace' => '']
     * @param Closure $routes 路由定义闭包
     * @return void
     * 
     * @example
     * ```php
     * $router->group(['prefix' => 'admin', 'middleware' => 'auth'], function($router) {
     *     $router->get('/dashboard', [AdminController::class, 'dashboard']);
     *     $router->get('/users', [AdminController::class, 'users']);
     * });
     * ```
     */
    public function group(array $attributes, Closure $routes): void
    {
        $this->groupStack[] = $attributes;

        $routes($this);

        array_pop($this->groupStack);
    }

    /**
     * 为 URI 添加分组前缀
     * 
     * @param string $uri 原始 URI
     * @return string 添加前缀后的 URI
     */
    protected function prefixUri(string $uri): string
    {
        $prefix = $this->getGroupPrefix();

        return $prefix . '/' . trim($uri, '/');
    }

    /**
     * 获取当前分组栈的合并前缀
     * 
     * @return string 合并后的前缀
     */
    protected function getGroupPrefix(): string
    {
        $prefix = '';

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }

        return $prefix;
    }

    /**
     * 将分组属性合并到路由
     * 
     * @param Route $route 路由实例
     * @return void
     */
    protected function mergeGroupAttributes(Route $route): void
    {
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $route->middleware($group['middleware']);
            }

            if (isset($group['namespace'])) {
                $route->namespace($group['namespace']);
            }
        }
    }

    /**
     * 注册中间件别名
     * 
     * 允许使用简短别名代替完整类名
     * 
     * @param string $name 中间件别名
     * @param string $class 中间件完整类名
     * @return static
     * 
     * @example
     * ```php
     * $router->aliasMiddleware('auth', AuthMiddleware::class);
     * $router->get('/admin', [AdminController::class, 'index'])->middleware('auth');
     * ```
     */
    public function aliasMiddleware(string $name, string $class): static
    {
        $this->middleware[$name] = $class;

        return $this;
    }

    /**
     * 分发请求到匹配的路由
     * 
     * 根据请求方法和路径查找匹配的路由并执行
     * 
     * @param Request $request HTTP 请求实例
     * @return Response HTTP 响应实例
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        // 查找匹配的路由
        $route = $this->findRoute($method, $path);

        if ($route === null) {
            return Response::error('Not Found', 404, 404);
        }

        return $this->runRoute($request, $route);
    }

    /**
     * 根据方法和路径查找匹配的路由
     * 
     * 支持正则表达式匹配和参数提取
     * 
     * @param string $method HTTP 方法
     * @param string $path 请求路径
     * @return Route|null 匹配的路由或 null
     */
    protected function findRoute(string $method, string $path): ?Route
    {
        $path = '/' . trim($path, '/');

        if (!isset($this->routes[$method])) {
            return null;
        }

        // 精确匹配
        foreach ($this->routes[$method] as $uri => $route) {
            $pattern = $this->convertToPattern($uri);

            if (preg_match($pattern, $path, $matches)) {
                // 提取命名参数
                $params = array_filter($matches, fn($key) => !is_numeric($key), ARRAY_FILTER_USE_KEY);
                $route->setParameters($params);

                return $route;
            }
        }

        return null;
    }

    /**
     * 将路由 URI 转换为正则表达式模式
     * 
     * 支持 {param} 和 {param?} 两种参数格式
     * 
     * @param string $uri 路由 URI
     * @return string 正则表达式
     */
    protected function convertToPattern(string $uri): string
    {
        $uri = '/' . trim($uri, '/');

        // 将 {param} 转换为命名捕获组
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $uri);

        // 将 {param?} 转换为可选命名捕获组
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\?\}/', '(?P<$1>[^/]*)?', $pattern);

        return '#^' . $pattern . '$#';
    }

    /**
     * 运行匹配的路由
     * 
     * 处理中间件管道并执行路由动作
     * 
     * @param Request $request HTTP 请求
     * @param Route $route 匹配的路由
     * @return Response HTTP 响应
     */
    protected function runRoute(Request $request, Route $route): Response
    {
        $action = $route->getAction();
        $parameters = $route->getParameters();

        // 解析中间件
        $middlewareStack = $this->resolveMiddleware($route->getMiddleware());

        // 构建管道
        $pipeline = array_reduce(
            array_reverse($middlewareStack),
            fn($next, $middleware) => fn($request) => $middleware->handle($request, $next),
            fn($request) => $this->runAction($action, $request, $parameters)
        );

        return $pipeline($request);
    }

    /**
     * 执行路由动作
     * 
     * 支持闭包、控制器数组和 Controller@method 字符串格式
     * 
     * @param Closure|array|string $action 路由动作
     * @param Request $request HTTP 请求
     * @param array $parameters 路由参数
     * @return Response HTTP 响应
     */
    protected function runAction(Closure|array|string $action, Request $request, array $parameters): Response
    {
        if ($action instanceof Closure) {
            $result = $action($request, ...array_values($parameters));
        } elseif (is_array($action)) {
            [$controller, $method] = $action;
            $controller = $this->container->make($controller);
            $result = $controller->{$method}($request, ...$parameters);
        } else {
            // 字符串格式 Controller@method
            [$controller, $method] = explode('@', $action);
            $controller = $this->container->make($controller);
            $result = $controller->{$method}($request, ...$parameters);
        }

        return $this->prepareResponse($result);
    }

    /**
     * 将路由动作的返回值转换为 Response 对象
     * 
     * 支持字符串、数组和 Response 对象
     * 
     * @param mixed $result 路由动作返回值
     * @return Response HTTP 响应
     */
    protected function prepareResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }

        return Response::make((string) $result);
    }

    /**
     * 解析中间件别名为实例
     * 
     * @param array $middleware 中间件别名或类名数组
     * @return array 中间件实例数组
     */
    protected function resolveMiddleware(array $middleware): array
    {
        $resolved = [];

        foreach ($middleware as $name) {
            $class = $this->middleware[$name] ?? $name;
            $resolved[] = $this->container->make($class);
        }

        return $resolved;
    }

    /**
     * 获取所有已注册的路由
     * 
     * @return array 路由数组
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
