<?php

/**
 * HolySword Framework - Web 路由配置文件
 * 
 * 该文件定义应用程序的 Web 路由。
 * 与 API 路由不同，Web 路由不会自动添加前缀。
 * 
 * 路由定义示例：
 * ```php
 * // 基础路由
 * $router->get('/', [HomeController::class, 'index']);
 * 
 * // 带参数的路由
 * $router->get('/users/{id}', [UserController::class, 'show']);
 * 
 * // 使用闭包
 * $router->get('/hello/{name}', function ($request, $name) {
 *     return Response::success(['greeting' => "Hello, {$name}!"]);
 * });
 * ```
 * 
 * @package    HolySword
 * @subpackage Routes
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 * @var \HolySword\Routing\Router $router 路由器实例
 */

declare(strict_types=1);

use HolySword\Http\Response;
use App\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| Web 路由
|--------------------------------------------------------------------------
|
| 在此定义你的 Web 路由。这些路由不会添加任何前缀。
|
*/

$router->get('/', function () {
    return Response::json([
        'message' => 'Welcome to HolySword Framework',
        'version' => '1.0.0',
    ]);
});

$router->get('/hello/{name}', function ($request, $name) {
    return Response::success([
        'greeting' => "Hello, {$name}!",
    ]);
});

// 示例：使用控制器
$router->get('/home', [HomeController::class, 'index']);

// 添加 about 路由，指向 HomeController 的 about 方法
$router->get('/about', [HomeController::class, 'about']);

/*
|--------------------------------------------------------------------------
| Web 路由列表
|--------------------------------------------------------------------------
|
| - GET /                    欢迎页面
| - GET /hello/{name}        打招呼示例
| - GET /home                首页控制器
| - GET /about               关于页面
|
*/