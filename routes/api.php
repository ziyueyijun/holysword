<?php

/**
 * HolySword - API 路由配置文件
 * 
 * 该文件定义应用程序的 API 路由。
 * 所有在此文件中定义的路由都将自动添加 /api 前缀。
 * 
 * @package    HolySword
 * @subpackage Routes
 * @var \HolySword\Routing\Router $router 路由器实例
 */

declare(strict_types=1);

use HolySword\Http\Response;

/*
|--------------------------------------------------------------------------
| 系统基础路由
|--------------------------------------------------------------------------
| 
| 系统健康检查、版本信息、数据库连接测试等基础接口
|
*/

/**
 * GET /api/status - 系统状态检查
 * 
 * 用于监控系统运行状态，返回基本的健康信息
 */
$router->get('/status', function () {
    return Response::success([
        'status' => 'running',
        'timestamp' => time(),
        'app' => 'HolySword',
    ]);
});

/**
 * GET /api/version - 获取版本信息
 * 
 * 返回应用程序版本、PHP版本等信息
 */
$router->get('/version', function () {
    return Response::success([
        'app' => 'HolySword',
        'version' => '1.0.0',
        'php' => PHP_VERSION,
    ]);
});

/**
 * GET /api/db-test - 数据库连接测试
 * 
 * 测试数据库连接是否正常，用于开发调试
 */
$router->get('/db-test', function () {
    try {
        // 获取PDO连接
        $pdo = db()->getConnection();
        
        // 简单查询测试
        $result = $pdo->query("SELECT 1 as test")->fetch();
        
        return Response::success([
            'message' => '数据库连接正常',
            'db_connected' => true,
        ]);
        
    } catch (\PDOException $e) {
        return Response::json([
            'success' => false,
            'message' => '数据库连接失败',
            'error' => $e->getMessage(),
        ], 500);
    } catch (\Throwable $e) {
        return Response::json([
            'success' => false,
            'message' => '系统错误',
            'error' => $e->getMessage(),
        ], 500);
    }
});

/*
|--------------------------------------------------------------------------
| 用户认证路由（待开发）
|--------------------------------------------------------------------------
| 
| 用户注册、登录、登出、密码重置等认证相关接口
| 
| 示例：
| $router->post('/register', [AuthController::class, 'register']);
| $router->post('/login', [AuthController::class, 'login']);
| $router->post('/logout', [AuthController::class, 'logout']);
|
*/

/*
|--------------------------------------------------------------------------
| 后台管理路由（待开发）
|--------------------------------------------------------------------------
| 
| 后台管理员认证、用户管理、系统设置等管理接口
| 
| 示例：
| $router->post('/admin/login', [AdminController::class, 'login']);
| $router->get('/admin/users', [UserController::class, 'index']);
|
*/

/*
|--------------------------------------------------------------------------
| 业务功能路由（待开发）
|--------------------------------------------------------------------------
| 
| 根据具体业务需求添加相关接口
|
*/
