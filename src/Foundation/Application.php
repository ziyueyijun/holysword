<?php

/**
 * HolySword Framework - 应用核心类
 * 
 * 该文件是 HolySword 框架的核心，继承自容器类并提供：
 * - 应用程序初始化和引导
 * - 核心服务注册（配置、路由、请求）
 * - 路径管理（应用目录、配置目录等）
 * - 请求处理和响应发送
 * - 异常处理
 * 
 * @package    HolySword
 * @subpackage Foundation
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Foundation;

use HolySword\Config\Config;
use HolySword\Container\Container;
use HolySword\Http\Request;
use HolySword\Http\Response;
use HolySword\Routing\Router;

/**
 * 应用核心类
 * 
 * HolySword 框架的主应用类，负责管理应用程序的生命周期。
 * 继承自 Container 类，提供完整的依赖注入能力。
 * 
 * 使用示例:
 * ```php
 * $app = new Application(__DIR__);
 * $app->run();
 * ```
 * 
 * @package HolySword\Foundation
 */
class Application extends Container
{
    /**
     * 框架版本号
     * 
     * @var string
     */
    public const VERSION = '1.0.0';

    /**
     * 应用根目录
     * 
     * 存储应用程序的根路径，所有其他路径都基于此目录
     * 
     * @var string
     */
    protected string $basePath;

    /**
     * 是否已引导
     * 
     * 标识应用是否已完成引导过程（路由加载等）
     * 
     * @var bool
     */
    protected bool $booted = false;

    /**
     * 已注册的服务提供者
     * 
     * 存储所有已注册的服务提供者实例
     * 
     * @var array
     */
    protected array $serviceProviders = [];

    /**
     * 已加载的服务提供者
     * 
     * 记录已加载的服务提供者类名，防止重复加载
     * 
     * @var array
     */
    protected array $loadedProviders = [];

    /**
     * 构造函数
     * 
     * 初始化应用并注册基础服务
     * 
     * @param string $basePath 应用根目录路径
     */
    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->loadEnvironmentVariables();
        $this->registerBaseBindings();
        $this->registerCoreServices();
    }

    /**
     * 注册基础绑定
     */
    protected function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->instance(Application::class, $this);
    }

    /**
     * 加载环境变量
     * 
     * 从 .env 文件加载环境变量到 $_ENV 和 getenv() 中
     * 
     * @return void
     */
    protected function loadEnvironmentVariables(): void
    {
        $envFile = $this->basePath . '/.env';
        
        if (!file_exists($envFile)) {
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // 跳过注释行
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // 解析键值对
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // 移除值两端的引号
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                // 设置环境变量
                if (!empty($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }

    /**
     * 注册核心服务
     */
    protected function registerCoreServices(): void
    {
        // 注册配置服务
        $this->singleton('config', function () {
            return new Config($this->configPath());
        });

        // 注册路由服务
        $this->singleton('router', function () {
            return new Router($this);
        });

        // 注册请求服务
        $this->singleton('request', function () {
            return Request::capture();
        });
    }

    /**
     * 获取应用根目录
     * 
     * @param string $path 可选的子路径
     * @return string 完整路径
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * 获取应用目录 (app/)
     * 
     * @param string $path 可选的子路径
     * @return string 完整路径
     */
    public function appPath(string $path = ''): string
    {
        return $this->basePath('app') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * 获取配置目录 (config/)
     * 
     * @param string $path 可选的子路径
     * @return string 完整路径
     */
    public function configPath(string $path = ''): string
    {
        return $this->basePath('config') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * 获取公共目录 (public/)
     * 
     * @param string $path 可选的子路径
     * @return string 完整路径
     */
    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * 获取路由目录 (routes/)
     * 
     * @param string $path 可选的子路径
     * @return string 完整路径
     */
    public function routesPath(string $path = ''): string
    {
        return $this->basePath('routes') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * 获取配置管理器实例
     * 
     * @return Config 配置管理器
     */
    public function config(): Config
    {
        return $this->make('config');
    }

    /**
     * 获取路由器实例
     * 
     * @return Router 路由器
     */
    public function router(): Router
    {
        return $this->make('router');
    }

    /**
     * 引导应用
     * 
     * 执行应用引导过程，包括加载路由等初始化操作
     * 
     * @return void
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // 加载路由
        $this->loadRoutes();

        $this->booted = true;
    }

    /**
     * 加载路由文件
     * 
     * 加载 web.php 和 api.php 路由文件
     * API 路由会自动添加 /api 前缀
     * 
     * @return void
     */
    protected function loadRoutes(): void
    {
        $router = $this->router();

        $webRoutes = $this->routesPath('web.php');
        $apiRoutes = $this->routesPath('api.php');

        if (file_exists($webRoutes)) {
            require $webRoutes;
        }

        if (file_exists($apiRoutes)) {
            $router->group(['prefix' => 'api'], function ($router) use ($apiRoutes) {
                require $apiRoutes;
            });
        }
    }

    /**
     * 处理请求并返回响应
     * 
     * 完整的请求处理流程，包括引导应用、路由分发和异常处理
     * 
     * @param Request $request HTTP 请求对象
     * @return Response HTTP 响应对象
     */
    public function handle(Request $request): Response
    {
        try {
            $this->boot();

            return $this->router()->dispatch($request);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * 处理异常
     * 
     * 将异常转换为 HTTP 响应。
     * 调试模式下显示详细错误信息，生产环境下隐藏。
     * 
     * @param \Throwable $e 异常对象
     * @return Response 错误响应
     */
    protected function handleException(\Throwable $e): Response
    {
        $debug = $this->config()->get('app.debug', false);

        if ($debug) {
            return Response::json([
                'code' => 500,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }

        return Response::error('服务器内部错误', 500, 500);
    }

    /**
     * 运行应用
     * 
     * 捆绑应用请求/响应循环，获取请求、处理并发送响应
     * 
     * @return void
     */
    public function run(): void
    {
        $request = $this->make('request');

        $response = $this->handle($request);

        $response->send();
    }

    /**
     * 判断是否为生产环境
     * 
     * @return bool 是否为生产环境
     */
    public function isProduction(): bool
    {
        return $this->config()->get('app.env') === 'production';
    }

    /**
     * 判断是否为本地环境
     * 
     * @return bool 是否为本地环境
     */
    public function isLocal(): bool
    {
        return $this->config()->get('app.env') === 'local';
    }

    /**
     * 获取框架版本号
     * 
     * @return string 版本号
     */
    public function version(): string
    {
        return static::VERSION;
    }
}
