<?php

/**
 * HolySword - Web 路由配置文件
 * 
 * 该文件定义应用程序的 Web 路由。
 * 与 API 路由不同，Web 路由不会自动添加前缀。
 * 
 * @package    HolySword
 * @subpackage Routes
 * @var \HolySword\Routing\Router $router 路由器实例
 */

declare(strict_types=1);

use HolySword\Http\Response;

/*
|--------------------------------------------------------------------------
| Web 路由
|--------------------------------------------------------------------------
|
| 在此定义你的 Web 路由。这些路由不会添加任何前缀。
| 前端页面都是静态HTML文件，放在public目录下，直接访问即可。
|
*/

/**
 * GET / - 首页重定向
 * 
 * 将根路径重定向到静态首页 index.html
 * 前端页面都是静态HTML文件，放在 public 目录下
 */
$router->get('/', function () {
    return Response::redirect('/index.html');
});