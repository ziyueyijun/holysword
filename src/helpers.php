<?php

/**
 * HolySword Framework - 全局辅助函数
 * 
 * 该文件定义了框架的全局辅助函数，这些函数可在应用的任何位置直接使用。
 * 通过 Composer 的 autoload.files 配置自动加载。
 * 
 * 包含的辅助函数：
 * - app()        : 获取应用实例或服务
 * - config()     : 获取配置值
 * - response()   : 创建 HTTP 响应
 * - json()       : 创建 JSON 响应
 * - redirect()   : 创建重定向响应
 * - base_path()  : 获取应用根目录
 * - app_path()   : 获取 app 目录
 * - config_path(): 获取配置目录
 * - public_path(): 获取公共目录
 * - env()        : 获取环境变量
 * - value()      : 解析值或闭包
 * - dd()         : 调试打印并终止
 * - dump()       : 调试打印
 * 
 * @package    HolySword
 * @subpackage Helpers
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

use HolySword\Container\Container;
use HolySword\Foundation\Application;

if (!function_exists('app')) {
    /**
     * 获取应用实例或从容器中解析服务
     * 
     * 这是访问服务容器的主要入口，允许你在应用的任何位置
     * 获取容器实例或从容器中解析特定的服务。
     * 
     * @param string|null $abstract 要解析的服务名称，null 返回容器实例
     * @param array $parameters 传递给服务构造函数的参数
     * @return mixed 容器实例或解析后的服务
     * 
     * @example
     * ```php
     * // 获取容器实例
     * $container = app();
     * 
     * // 解析服务
     * $router = app('router');
     * $config = app('config');
     * 
     * // 传递参数
     * $service = app(MyService::class, ['option' => 'value']);
     * ```
     */
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('config')) {
    /**
     * 获取配置值
     * 
     * 使用点表示法访问嵌套配置项。
     * 配置文件位于 config/ 目录下。
     * 
     * @param string $key 配置键名，格式：文件名.配置项.子配置项
     * @param mixed $default 配置不存在时的默认值
     * @return mixed 配置值或默认值
     * 
     * @example
     * ```php
     * // 获取 config/app.php 中的 name 配置
     * $appName = config('app.name');
     * 
     * // 带默认值
     * $debug = config('app.debug', false);
     * 
     * // 获取嵌套配置
     * $dbHost = config('database.mysql.host', 'localhost');
     * ```
     */
    function config(string $key, mixed $default = null): mixed
    {
        return app('config')->get($key, $default);
    }
}

if (!function_exists('response')) {
    /**
     * 创建 HTTP 响应实例
     * 
     * 创建并返回一个新的 Response 对象，用于返回纯文本或 HTML 内容。
     * 
     * @param string $content 响应体内容
     * @param int $status HTTP 状态码，默认 200
     * @param array $headers 额外的响应头
     * @return \HolySword\Http\Response 响应实例
     * 
     * @example
     * ```php
     * // 简单文本响应
     * return response('Hello World');
     * 
     * // 带状态码
     * return response('Created', 201);
     * 
     * // 带自定义响应头
     * return response('<html>...</html>', 200, [
     *     'Content-Type' => 'text/html',
     * ]);
     * ```
     */
    function response(string $content = '', int $status = 200, array $headers = []): \HolySword\Http\Response
    {
        return \HolySword\Http\Response::make($content, $status, $headers);
    }
}

if (!function_exists('json')) {
    /**
     * 创建 JSON 格式的 HTTP 响应
     * 
     * 将数据自动序列化为 JSON 并设置正确的 Content-Type 头。
     * 支持 Unicode 字符（中文不会被转义）。
     * 
     * @param mixed $data 要序列化为 JSON 的数据
     * @param int $status HTTP 状态码，默认 200
     * @param array $headers 额外的响应头
     * @return \HolySword\Http\Response JSON 响应实例
     * 
     * @example
     * ```php
     * // 返回 JSON 数据
     * return json(['name' => '张三', 'age' => 25]);
     * 
     * // 带状态码
     * return json(['id' => 1], 201);
     * 
     * // 带自定义响应头
     * return json($data, 200, ['X-Custom-Header' => 'value']);
     * ```
     */
    function json(mixed $data, int $status = 200, array $headers = []): \HolySword\Http\Response
    {
        return \HolySword\Http\Response::json($data, $status, $headers);
    }
}

if (!function_exists('redirect')) {
    /**
     * 创建 HTTP 重定向响应
     * 
     * 生成一个将用户重定向到指定 URL 的响应。
     * 
     * @param string $url 重定向目标 URL
     * @param int $status HTTP 状态码，302=临时重定向，301=永久重定向
     * @return \HolySword\Http\Response 重定向响应实例
     * 
     * @example
     * ```php
     * // 临时重定向
     * return redirect('/login');
     * 
     * // 永久重定向（用于 URL 迁移）
     * return redirect('/new-page', 301);
     * 
     * // 重定向到外部网址
     * return redirect('https://example.com');
     * ```
     */
    function redirect(string $url, int $status = 302): \HolySword\Http\Response
    {
        return \HolySword\Http\Response::redirect($url, $status);
    }
}

if (!function_exists('base_path')) {
    /**
     * 获取应用根目录路径
     * 
     * 返回应用程序的根目录绝对路径，可选拼接子路径。
     * 
     * @param string $path 要拼接的子路径
     * @return string 完整的绝对路径
     * 
     * @example
     * ```php
     * // 获取根目录
     * $root = base_path();  // /var/www/app
     * 
     * // 获取子路径
     * $vendor = base_path('vendor');  // /var/www/app/vendor
     * ```
     */
    function base_path(string $path = ''): string
    {
        return app()->basePath($path);
    }
}

if (!function_exists('app_path')) {
    /**
     * 获取应用目录 (app/) 路径
     * 
     * 返回 app 目录的绝对路径，该目录包含应用的控制器、模型等。
     * 
     * @param string $path 要拼接的子路径
     * @return string 完整的绝对路径
     * 
     * @example
     * ```php
     * // 获取 app 目录
     * $app = app_path();  // /var/www/app/app
     * 
     * // 获取控制器目录
     * $controllers = app_path('Controllers');  // /var/www/app/app/Controllers
     * ```
     */
    function app_path(string $path = ''): string
    {
        return app()->appPath($path);
    }
}

if (!function_exists('config_path')) {
    /**
     * 获取配置目录 (config/) 路径
     * 
     * 返回 config 目录的绝对路径，该目录存放应用的配置文件。
     * 
     * @param string $path 要拼接的子路径
     * @return string 完整的绝对路径
     * 
     * @example
     * ```php
     * // 获取 config 目录
     * $config = config_path();  // /var/www/app/config
     * 
     * // 获取指定配置文件路径
     * $dbConfig = config_path('database.php');  // /var/www/app/config/database.php
     * ```
     */
    function config_path(string $path = ''): string
    {
        return app()->configPath($path);
    }
}

if (!function_exists('public_path')) {
    /**
     * 获取公共目录 (public/) 路径
     * 
     * 返回 public 目录的绝对路径，该目录是 Web 服务器的根目录。
     * 存放公共可访问的静态资源（CSS、JS、图片等）。
     * 
     * @param string $path 要拼接的子路径
     * @return string 完整的绝对路径
     * 
     * @example
     * ```php
     * // 获取 public 目录
     * $public = public_path();  // /var/www/app/public
     * 
     * // 获取静态资源路径
     * $css = public_path('css/app.css');  // /var/www/app/public/css/app.css
     * ```
     */
    function public_path(string $path = ''): string
    {
        return app()->publicPath($path);
    }
}

if (!function_exists('env')) {
    /**
     * 获取环境变量值
     * 
     * 从环境变量中读取配置值，支持特殊值转换：
     * - 'true'/'(true)' 转换为 boolean true
     * - 'false'/'(false)' 转换为 boolean false
     * - 'empty'/'(empty)' 转换为空字符串
     * - 'null'/'(null)' 转换为 null
     * 
     * @param string $key 环境变量名称
     * @param mixed $default 环境变量不存在时的默认值
     * @return mixed 环境变量值或默认值
     * 
     * @example
     * ```php
     * // 获取环境变量
     * $appEnv = env('APP_ENV', 'production');
     * $debug = env('APP_DEBUG', false);  // 返回 boolean
     * 
     * // 在 .env 文件中定义
     * // APP_DEBUG=true
     * // DB_HOST=localhost
     * ```
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}

if (!function_exists('value')) {
    /**
     * 解析值，如果是闭包则执行
     * 
     * 用于延迟求值，只有在需要时才执行闭包。
     * 如果不是闭包，则直接返回原值。
     * 
     * @param mixed $value 要解析的值或闭包
     * @param mixed ...$args 传递给闭包的参数
     * @return mixed 解析后的值
     * 
     * @example
     * ```php
     * // 普通值直接返回
     * $result = value(42);  // 42
     * 
     * // 闭包会被执行
     * $result = value(function() {
     *     return expensive_computation();
     * });
     * 
     * // 传递参数给闭包
     * $result = value(fn($x, $y) => $x + $y, 1, 2);  // 3
     * ```
     */
    function value(mixed $value, ...$args): mixed
    {
        return $value instanceof \Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('dd')) {
    /**
     * 调试打印变量并终止执行
     * 
     * “Dump and Die” 的缩写，打印一个或多个变量的详细信息后立即终止脚本执行。
     * 仅用于开发调试，不应在生产环境中使用。
     * 
     * @param mixed ...$vars 要打印的变量（可传多个）
     * @return never 永不返回，始终终止执行
     * 
     * @example
     * ```php
     * // 调试单个变量
     * dd($user);
     * 
     * // 调试多个变量
     * dd($request, $response, $data);
     * ```
     */
    function dd(mixed ...$vars): never
    {
        foreach ($vars as $var) {
            var_dump($var);
        }
        exit(1);
    }
}

if (!function_exists('dump')) {
    /**
     * 调试打印变量（不终止执行）
     * 
     * 打印一个或多个变量的详细信息，但不终止脚本执行。
     * 用于在不中断程序流程的情况下查看变量状态。
     * 
     * @param mixed ...$vars 要打印的变量（可传多个）
     * @return void
     * 
     * @example
     * ```php
     * // 打印并继续执行
     * dump($user);
     * // ... 继续执行代码
     * 
     * // 打印多个变量
     * dump($a, $b, $c);
     * ```
     */
    function dump(mixed ...$vars): void
    {
        foreach ($vars as $var) {
            var_dump($var);
        }
    }
}

if (!function_exists('validate')) {
    /**
     * 创建验证器实例
     * 
     * @param array $data 要验证的数据
     * @param array $rules 验证规则
     * @param array $messages 自定义错误消息
     * @return \HolySword\Validation\Validator
     * 
     * @example
     * ```php
     * $validator = validate($request->all(), [
     *     'name' => 'required|min:2',
     *     'email' => 'required|email',
     * ]);
     * 
     * if ($validator->fails()) {
     *     return Response::error($validator->firstError(), 422, 422);
     * }
     * ```
     */
    function validate(array $data, array $rules, array $messages = []): \HolySword\Validation\Validator
    {
        return \HolySword\Validation\Validator::make($data, $rules, $messages);
    }
}

if (!function_exists('logger')) {
    /**
     * 获取日志记录器实例
     * 
     * @param string|null $channel 日志通道名称，用于区分不同功能模块
     * @return \HolySword\Log\Logger
     * 
     * @example
     * ```php
     * // 默认通道 (app)
     * logger()->info('用户登录', ['user_u' => 1]);
     * 
     * // 支付通道
     * logger('payment')->info('支付请求', ['order_no' => '12345']);
     * 
     * // 认证通道
     * logger('auth')->error('登录失败', ['ip' => '127.0.0.1']);
     * 
     * // 支持的通道: app, payment, auth, vip, admin, error
     * ```
     */
    function logger(?string $channel = null): \HolySword\Log\Logger
    {
        static $loggers = [];
        
        // 默认通道
        $channel = $channel ?? 'app';
        
        // 如果该通道的日志器已存在，直接返回
        if (!isset($loggers[$channel])) {
            // 所有日志只写入到根目录的 logs 文件夹
            $logPath = base_path('logs');
            $loggers[$channel] = new \HolySword\Log\Logger($logPath, $channel);
        }
        
        return $loggers[$channel];
    }
}

if (!function_exists('db')) {
    /**
     * 获取数据库连接实例
     * 
     * 支持多数据库连接，配置从 .env 文件读取。
     * 
     * @param string|null $connection 连接名称，null 使用默认连接
     * @return \HolySword\Database\DB
     * 
     * @example
     * ```php
     * // 使用默认连接
     * $users = db()->query('SELECT * FROM users');
     * 
     * // 使用指定连接
     * $data = db('mysql')->query('SELECT * FROM orders');
     * $pgData = db('pgsql')->query('SELECT * FROM products');
     * 
     * // 使用 MySQL 从库（读写分离）
     * $readData = db('mysql_read')->query('SELECT * FROM logs');
     * ```
     */
    function db(?string $connection = null): \HolySword\Database\DB
    {
        return \HolySword\Database\DatabaseManager::connection($connection);
    }
}

if (!function_exists('app_config_meta')) {
    /**
     * 生成应用配置的 meta 标签
     * 用于将后端配置传递给前端 JavaScript
     * 
     * @return string HTML meta 标签
     * 
     * @example
     * ```php
     * // 在 HTML 模板中使用
     * <!DOCTYPE html>
     * <html>
     * <head>
     *     <?= app_config_meta() ?>
     * </head>
     * ```
     */
    function app_config_meta(): string
    {
        $appUrl = env('APP_URL', 'http://localhost');
        $appName = env('APP_NAME', 'HolySword');
        
        // 移除末尾的斜杠
        $appUrl = rtrim($appUrl, '/');
        
        return sprintf(
            '<meta name="app-url" content="%s">' . "\n" .
            '<meta name="api-base-url" content="%s">' . "\n" .
            '<meta name="app-name" content="%s">',
            htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($appName, ENT_QUOTES, 'UTF-8')
        );
    }
}

if (!function_exists('safe_error_message')) {
    /**
     * 获取安全的错误消息
     * 
     * 在调试模式下返回详细错误信息，生产环境返回通用消息
     * 
     * @param \Throwable $e 异常对象
     * @param string $prefix 错误消息前缀
     * @param string $fallback 生产环境的回退消息
     * @return string 安全的错误消息
     * 
     * @example
     * ```php
     * // 调试模式: "转换失败: File not found in /app/index.php:42"
     * // 生产模式: "转换失败"
     * return Response::error(safe_error_message($e, '转换失败'), 5001, 500);
     * ```
     */
    function safe_error_message(\Throwable $e, string $prefix = '', string $fallback = '操作失败'): string
    {
        if (env('APP_DEBUG', false)) {
            // 调试模式：显示详细错误信息，包括文件和行号
            $debugInfo = $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine();
            return $prefix ? $prefix . ': ' . $debugInfo : $debugInfo;
        }
        // 生产模式：返回通用消息
        return $prefix ?: $fallback;
    }
}

if (!function_exists('class_basename')) {
    /**
     * 获取类的基础名称（不含命名空间）
     * 
     * 从完整的类名中提取简短类名，去除命名空间部分。
     * 
     * @param object|string $class 类对象或类名字符串
     * @return string 类的基础名称
     * 
     * @example
     * ```php
     * // 从完整类名获取简短名
     * class_basename('App\\Models\\User');  // 'User'
     * 
     * // 从对象获取类名
     * class_basename($userModel);  // 'User'
     * ```
     */
    function class_basename(object|string $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}
