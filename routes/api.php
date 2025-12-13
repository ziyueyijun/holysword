<?php

/**
 * HolySword Framework - API 路由配置文件
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
use App\Controllers\DemoController;
use App\Controllers\HomeController;
use App\Middleware\LogMiddleware;

/*
|--------------------------------------------------------------------------
| 基础 API 路由
|--------------------------------------------------------------------------
*/

// GET /api/status - 系统状态
$router->get('/status', function () {
    return Response::success([
        'status' => 'running',
        'timestamp' => time(),
    ]);
});

// GET /api/version - 版本信息
$router->get('/version', function () {
    return Response::success([
        'framework' => 'HolySword',
        'version' => '1.0.0',
        'php' => PHP_VERSION,
    ]);
});

// GET /api/about - 关于页面
$router->get('/about', [HomeController::class, 'about']);

// GET /api/routes - 所有测试路由汇总
$router->get('/routes', function () {
    return Response::success([
        'message' => 'HolySword Framework 测试路由汇总',
        'test_page' => 'http://localhost/test.html',
        'routes' => [
            '基础路由' => [
                'GET /' => '首页欢迎',
                'GET /api' => 'API 欢迎',
                'GET /api/health' => '健康检查',
                'GET /api/version' => '版本信息',
            ],
            '请求处理' => [
                'GET /api/demo/request' => '获取请求基本信息',
                'GET /api/demo/query?name=John&age=25' => '获取 Query 参数',
                'POST /api/demo/post' => '获取 POST 数据',
                'POST /api/demo/json' => '获取 JSON 请求体',
                'GET /api/demo/headers' => '获取请求头',
                'GET /api/demo/users/{id}' => '路由参数示例',
            ],
            '数据验证' => [
                'POST /api/demo/validate' => '数据验证示例',
            ],
            '中间件' => [
                'GET /api/demo/protected?token=demo-token-12345' => '认证保护路由',
                'GET /api/demo/cors' => 'CORS 跨域路由',
                'GET /api/demo/log' => '日志记录示例',
            ],
            '数据库' => [
                'GET /api/demo/db/connections' => '查看数据库连接配置',
                'GET /api/demo/db/test' => '测试数据库连接',
                'GET /api/demo/db/active' => '查看活动连接',
                'GET /api/demo/db/switch' => '多连接演示',
            ],
            '查询构建器' => [
                'GET /api/demo/query/examples' => 'SQL 查询示例',
                'GET /api/demo/query/aggregates' => '聚合函数示例',
                'GET /api/demo/query/crud' => 'CRUD 操作示例',
                'GET /api/demo/query/helpers' => '辅助方法示例',
            ],
            '调试工具' => [
                'GET /api/demo/dump' => 'dump 调试输出',
            ],
        ],
    ]);
});

/*
|--------------------------------------------------------------------------
| 请求数据获取示例
|--------------------------------------------------------------------------
*/

// GET /api/demo/request - 获取请求基本信息
$router->get('/demo/request', [DemoController::class, 'requestInfo']);

// GET /api/demo/query?name=John&age=25 - 获取 Query 参数
$router->get('/demo/query', [DemoController::class, 'queryParams']);

// POST /api/demo/post - 获取 POST 数据
$router->post('/demo/post', [DemoController::class, 'postData']);

// POST /api/demo/json - 获取 JSON 请求体
$router->post('/demo/json', [DemoController::class, 'jsonData']);

// GET /api/demo/headers - 获取请求头
$router->get('/demo/headers', [DemoController::class, 'headers']);

// GET /api/demo/cookies - 获取 Cookie
$router->get('/demo/cookies', [DemoController::class, 'cookies']);

// POST /api/demo/input?query_param=1 - 混合获取数据
$router->post('/demo/input', [DemoController::class, 'inputData']);

// POST /api/demo/filter - 过滤数据 (only/except)
$router->post('/demo/filter', [DemoController::class, 'filterData']);

/*
|--------------------------------------------------------------------------
| 路由参数示例
|--------------------------------------------------------------------------
*/

// GET /api/demo/users/123 - 单个路由参数
$router->get('/demo/users/{id}', [DemoController::class, 'routeParams']);

// GET /api/demo/posts/123/comments/456 - 多个路由参数
$router->get('/demo/posts/{postId}/comments/{commentId}', [DemoController::class, 'multiParams']);

/*
|--------------------------------------------------------------------------
| RESTful CRUD 示例
|--------------------------------------------------------------------------
*/

// POST /api/demo/resources - 创建资源
$router->post('/demo/resources', [DemoController::class, 'store']);

// PUT /api/demo/resources/123 - 更新资源
$router->put('/demo/resources/{id}', [DemoController::class, 'update']);

// PATCH /api/demo/resources/123 - 部分更新资源
$router->patch('/demo/resources/{id}', [DemoController::class, 'update']);

// DELETE /api/demo/resources/123 - 删除资源
$router->delete('/demo/resources/{id}', [DemoController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| 响应功能示例
|--------------------------------------------------------------------------
*/

// GET /api/demo/error?type=404 - 错误响应示例
$router->get('/demo/error', [DemoController::class, 'errorDemo']);

// GET /api/demo/redirect?url=/api/status - 重定向示例
$router->get('/demo/redirect', [DemoController::class, 'redirectDemo']);

// GET /api/demo/cookie?name=test&value=123 - 设置 Cookie
$router->get('/demo/cookie', [DemoController::class, 'setCookie']);

// GET /api/demo/custom-headers - 自定义响应头
$router->get('/demo/custom-headers', [DemoController::class, 'customHeaders']);

// POST /api/demo/upload - 文件上传信息
$router->post('/demo/upload', [DemoController::class, 'uploadInfo']);

/*
|--------------------------------------------------------------------------
| 中间件示例
|--------------------------------------------------------------------------
*/

// 注册中间件别名
$router->aliasMiddleware('log', LogMiddleware::class);

// GET /api/demo/with-middleware - 带中间件的路由（查看响应头 X-Response-Time）
$router->get('/demo/with-middleware', function (\HolySword\Http\Request $request) {
    return Response::success([
        'message' => '这个路由使用了 LogMiddleware，查看响应头可以看到 X-Response-Time',
    ]);
})->middleware('log');

/*
|--------------------------------------------------------------------------
| 路由分组示例
|--------------------------------------------------------------------------
*/

// 分组路由 - 所有路由添加 /v2 前缀和 log 中间件
$router->group(['prefix' => 'v2', 'middleware' => 'log'], function ($router) {
    // GET /api/v2/status
    $router->get('/status', function () {
        return Response::success([
            'status' => 'running',
            'api_version' => 'v2',
            'timestamp' => time(),
        ]);
    });
    
    // GET /api/v2/info
    $router->get('/info', function () {
        return Response::success([
            'message' => '这是 v2 版本的 API，带有 log 中间件',
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| 闭包路由示例
|--------------------------------------------------------------------------
*/

// GET /api/demo/closure - 使用闭包处理请求
$router->get('/demo/closure', function (\HolySword\Http\Request $request) {
    return Response::success([
        'message' => '这是一个闭包路由',
        'method' => $request->method(),
        'path' => $request->path(),
    ]);
});

// GET /api/demo/closure/{name} - 闭包路由带参数
$router->get('/demo/closure/{name}', function (\HolySword\Http\Request $request, string $name) {
    return Response::success([
        'greeting' => "Hello, {$name}!",
        'message' => '这是带参数的闭包路由',
    ]);
});

/*
|--------------------------------------------------------------------------
| 多方法路由示例
|--------------------------------------------------------------------------
*/

// GET|POST /api/demo/match - 支持多种方法
$router->match(['GET', 'POST'], '/demo/match', function (\HolySword\Http\Request $request) {
    return Response::success([
        'method' => $request->method(),
        'message' => '这个路由支持 GET 和 POST 方法',
    ]);
});

// ANY /api/demo/any - 支持所有方法
$router->any('/demo/any', function (\HolySword\Http\Request $request) {
    return Response::success([
        'method' => $request->method(),
        'message' => '这个路由支持所有 HTTP 方法',
    ]);
});

/*
|--------------------------------------------------------------------------
| API 路由列表
|--------------------------------------------------------------------------
|
| 基础:
| - GET /api/status                            系统状态
| - GET /api/version                           版本信息
| - GET /api/about                             关于页面
|
| 请求数据:
| - GET /api/demo/request                      请求基本信息
| - GET /api/demo/query?name=John&age=25       Query参数
| - POST /api/demo/post                        POST数据
| - POST /api/demo/json                        JSON数据
| - GET /api/demo/headers                      请求头
| - GET /api/demo/cookies                      Cookie
| - POST /api/demo/input                       混合数据
| - POST /api/demo/filter                      过滤数据
|
| 路由参数:
| - GET /api/demo/users/123                    单参数
| - GET /api/demo/posts/1/comments/2           多参数
|
| RESTful:
| - POST /api/demo/resources                   创建
| - PUT /api/demo/resources/123                更新
| - PATCH /api/demo/resources/123              部分更新
| - DELETE /api/demo/resources/123             删除
|
| 响应功能:
| - GET /api/demo/error?type=404               错误响应
| - GET /api/demo/redirect?url=/api/status     重定向
| - GET /api/demo/cookie?name=test&value=123   设置Cookie
| - GET /api/demo/custom-headers               自定义头
| - POST /api/demo/upload                      文件上传
|
| 中间件:
| - GET /api/demo/with-middleware              日志中间件
|
| 路由分组:
| - GET /api/v2/status                         v2版本
| - GET /api/v2/info                           v2信息
|
| 闭包路由:
| - GET /api/demo/closure                      闭包路由
| - GET /api/demo/closure/{name}               带参数
|
| 多方法:
| - GET|POST /api/demo/match                   指定方法
| - ANY /api/demo/any                          所有方法
|
| 新功能:
| - POST /api/demo/validate                    数据验证
| - GET /api/demo/log                          日志记录
| - GET /api/demo/protected                    认证保护
|
*/

/*
|--------------------------------------------------------------------------
| 新功能测试路由
|--------------------------------------------------------------------------
*/

use App\Middleware\CorsMiddleware;
use App\Middleware\AuthMiddleware;
use HolySword\Validation\Validator;

// 注册新中间件
$router->aliasMiddleware('cors', CorsMiddleware::class);
$router->aliasMiddleware('auth', AuthMiddleware::class);

// POST /api/demo/validate - 数据验证示例
$router->post('/demo/validate', function (\HolySword\Http\Request $request) {
    $data = $request->json() ?: $request->all();
    
    $validator = Validator::make($data, [
        'name' => 'required|min:2|max:50',
        'email' => 'required|email',
        'age' => 'numeric|min:1|max:120',
    ]);
    
    if ($validator->fails()) {
        return Response::json([
            'code' => 422,
            'message' => $validator->firstError(),
            'errors' => $validator->errors(),
        ], 422);
    }
    
    return Response::success([
        'message' => '验证通过',
        'validated' => $validator->validated(),
    ]);
});

// GET /api/demo/log - 日志记录示例
$router->get('/demo/log', function (\HolySword\Http\Request $request) {
    $message = $request->query('message', '测试日志');
    $level = $request->query('level', 'info');
    
    $logger = logger();
    
    switch ($level) {
        case 'error':
            $logger->error($message, ['ip' => $request->ip()]);
            break;
        case 'warning':
            $logger->warning($message, ['ip' => $request->ip()]);
            break;
        case 'debug':
            $logger->debug($message, ['ip' => $request->ip()]);
            break;
        default:
            $logger->info($message, ['ip' => $request->ip()]);
    }
    
    return Response::success([
        'message' => '日志已记录',
        'level' => $level,
        'content' => $message,
        'log_path' => storage_path('logs'),
    ]);
});

// GET /api/demo/protected - 认证保护路由示例
// 访问时需要带 Token: ?token=demo-token-12345 或 Header: Authorization: Bearer demo-token-12345
$router->get('/demo/protected', function (\HolySword\Http\Request $request) {
    return Response::success([
        'message' => '认证成功，这是受保护的数据',
        'secret_data' => '此数据仅授权用户可见',
    ]);
})->middleware('auth');

// GET /api/demo/cors - CORS 路由示例
$router->get('/demo/cors', function (\HolySword\Http\Request $request) {
    return Response::success([
        'message' => '此路由支持跨域访问',
        'cors' => true,
    ]);
})->middleware('cors');

/*
|--------------------------------------------------------------------------
| 数据库多连接测试路由
|--------------------------------------------------------------------------
*/

use HolySword\Database\DatabaseManager;

// GET /api/demo/db/connections - 查看数据库连接配置
$router->get('/demo/db/connections', function (\HolySword\Http\Request $request) {
    $dbConfig = config('database');
    
    // 隐藏密码
    $connections = [];
    foreach ($dbConfig['connections'] ?? [] as $name => $config) {
        $connections[$name] = [
            'driver' => $config['driver'] ?? 'unknown',
            'host' => $config['host'] ?? 'N/A',
            'port' => $config['port'] ?? 'N/A',
            'database' => $config['database'] ?? 'N/A',
            'username' => $config['username'] ?? 'N/A',
        ];
    }
    
    return Response::success([
        'default' => $dbConfig['default'] ?? 'mysql',
        'connections' => $connections,
    ]);
});

// GET /api/demo/db/test?connection=mysql - 测试数据库连接
$router->get('/demo/db/test', function (\HolySword\Http\Request $request) {
    $connectionName = $request->query('connection');
    
    try {
        $db = db($connectionName);
        $pdo = $db->getConnection();
        
        // 执行简单查询测试连接
        $result = $pdo->query('SELECT 1 as test')->fetch();
        
        return Response::success([
            'connection' => $connectionName ?? 'default',
            'status' => 'connected',
            'server_info' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
            'driver' => $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME),
        ]);
    } catch (\Throwable $e) {
        return Response::json([
            'code' => 500,
            'message' => '数据库连接失败: ' . $e->getMessage(),
            'connection' => $connectionName ?? 'default',
        ], 500);
    }
});

// GET /api/demo/db/active - 查看活动连接
$router->get('/demo/db/active', function (\HolySword\Http\Request $request) {
    return Response::success([
        'active_connections' => DatabaseManager::getConnections(),
        'default_connection' => DatabaseManager::getDefaultConnection(),
    ]);
});

// GET /api/demo/db/switch - 演示切换连接
$router->get('/demo/db/switch', function (\HolySword\Http\Request $request) {
    $results = [];
    
    // 演示使用不同连接（仅配置展示，不实际连接）
    $connections = ['mysql', 'pgsql', 'sqlite'];
    
    foreach ($connections as $name) {
        $config = config("database.connections.{$name}");
        if ($config) {
            $results[$name] = [
                'driver' => $config['driver'] ?? 'unknown',
                'available' => true,
            ];
        }
    }
    
    return Response::success([
        'message' => '数据库多连接演示',
        'usage' => [
            'default' => 'db()->query("SELECT * FROM users")',
            'mysql' => 'db("mysql")->query("SELECT * FROM users")',
            'pgsql' => 'db("pgsql")->query("SELECT * FROM products")',
            'mysql_read' => 'db("mysql_read")->query("SELECT * FROM logs") // 从库读取',
        ],
        'connections' => $results,
    ]);
});

/*
|--------------------------------------------------------------------------
| 查询构建器示例路由
|--------------------------------------------------------------------------
| 这些路由展示了 QueryBuilder 的各种功能用法，无需真实数据库连接即可查看 SQL
*/

// GET /api/demo/query/examples - 查询构建器示例
$router->get('/demo/query/examples', function (\HolySword\Http\Request $request) {
    
    $examples = [
        // 基础查询
        'basic' => [
            'title' => '基础查询',
            'code' => "db()->table('users')->where('status', 1)->get()",
            'sql' => db()->table('users')->where('status', 1)->toSql(),
        ],
        
        // SELECT 指定字段
        'select' => [
            'title' => 'SELECT 指定字段',
            'code' => "db()->table('users')->select('id', 'name', 'email')->get()",
            'sql' => db()->table('users')->select('id', 'name', 'email')->toSql(),
        ],
        
        // WHERE 多条件
        'where_multiple' => [
            'title' => 'WHERE 多条件',
            'code' => "db()->table('users')->where('status', 1)->where('role', 'admin')->get()",
            'sql' => db()->table('users')->where('status', 1)->where('role', 'admin')->toSql(),
        ],
        
        // WHERE IN
        'where_in' => [
            'title' => 'WHERE IN',
            'code' => "db()->table('users')->whereIn('id', [1, 2, 3])->get()",
            'sql' => db()->table('users')->whereIn('id', [1, 2, 3])->toSql(),
        ],
        
        // WHERE BETWEEN
        'where_between' => [
            'title' => 'WHERE BETWEEN',
            'code' => "db()->table('users')->whereBetween('age', [18, 60])->get()",
            'sql' => db()->table('users')->whereBetween('age', [18, 60])->toSql(),
        ],
        
        // WHERE LIKE
        'where_like' => [
            'title' => 'WHERE LIKE',
            'code' => "db()->table('users')->whereLike('name', '%john%')->get()",
            'sql' => db()->table('users')->whereLike('name', '%john%')->toSql(),
        ],
        
        // WHERE NULL
        'where_null' => [
            'title' => 'WHERE IS NULL',
            'code' => "db()->table('users')->whereNull('deleted_at')->get()",
            'sql' => db()->table('users')->whereNull('deleted_at')->toSql(),
        ],
        
        // OR WHERE
        'or_where' => [
            'title' => 'OR WHERE',
            'code' => "db()->table('users')->where('role', 'admin')->orWhere('role', 'moderator')->get()",
            'sql' => db()->table('users')->where('role', 'admin')->orWhere('role', 'moderator')->toSql(),
        ],
        
        // LEFT JOIN
        'left_join' => [
            'title' => 'LEFT JOIN',
            'code' => "db()->table('orders')->select('orders.*', 'users.name as user_name')->leftJoin('users', 'orders.user_id', '=', 'users.id')->get()",
            'sql' => db()->table('orders')
                ->select('orders.*', 'users.name as user_name')
                ->leftJoin('users', 'orders.user_id', '=', 'users.id')
                ->toSql(),
        ],
        
        // INNER JOIN
        'inner_join' => [
            'title' => 'INNER JOIN',
            'code' => "db()->table('orders')->join('users', 'orders.user_id', '=', 'users.id')->get()",
            'sql' => db()->table('orders')
                ->join('users', 'orders.user_id', '=', 'users.id')
                ->toSql(),
        ],
        
        // 多表 JOIN
        'multiple_join' => [
            'title' => '多表 JOIN',
            'code' => "db()->table('orders')->leftJoin('users', 'orders.user_id', '=', 'users.id')->leftJoin('products', 'orders.product_id', '=', 'products.id')->get()",
            'sql' => db()->table('orders')
                ->leftJoin('users', 'orders.user_id', '=', 'users.id')
                ->leftJoin('products', 'orders.product_id', '=', 'products.id')
                ->toSql(),
        ],
        
        // ORDER BY
        'order_by' => [
            'title' => 'ORDER BY',
            'code' => "db()->table('users')->orderBy('created_at', 'desc')->orderBy('name')->get()",
            'sql' => db()->table('users')->orderBy('created_at', 'desc')->orderBy('name')->toSql(),
        ],
        
        // GROUP BY + HAVING
        'group_by' => [
            'title' => 'GROUP BY + HAVING',
            'code' => "db()->table('orders')->select('user_id', 'COUNT(*) as order_count')->groupBy('user_id')->having('order_count', '>', 5)->get()",
            'sql' => db()->table('orders')
                ->select('user_id', 'COUNT(*) as order_count')
                ->groupBy('user_id')
                ->having('order_count', '>', 5)
                ->toSql(),
        ],
        
        // LIMIT + OFFSET
        'pagination' => [
            'title' => '分页 (LIMIT + OFFSET)',
            'code' => "db()->table('users')->orderBy('id')->limit(10)->offset(20)->get()",
            'sql' => db()->table('users')->orderBy('id')->limit(10)->offset(20)->toSql(),
        ],
        
        // forPage 分页
        'for_page' => [
            'title' => 'forPage 分页',
            'code' => "db()->table('users')->forPage(3, 15)->get()  // 第3页，每页15条",
            'sql' => db()->table('users')->forPage(3, 15)->toSql(),
        ],
        
        // 复杂查询示例
        'complex' => [
            'title' => '复杂查询示例',
            'code' => "db()->table('orders')\n"
                . "    ->select('orders.id', 'orders.amount', 'users.name', 'products.title')\n"
                . "    ->leftJoin('users', 'orders.user_id', '=', 'users.id')\n"
                . "    ->leftJoin('products', 'orders.product_id', '=', 'products.id')\n"
                . "    ->where('orders.status', 'completed')\n"
                . "    ->whereBetween('orders.amount', [100, 1000])\n"
                . "    ->orderBy('orders.created_at', 'desc')\n"
                . "    ->limit(10)\n"
                . "    ->get()",
            'sql' => db()->table('orders')
                ->select('orders.id', 'orders.amount', 'users.name', 'products.title')
                ->leftJoin('users', 'orders.user_id', '=', 'users.id')
                ->leftJoin('products', 'orders.product_id', '=', 'products.id')
                ->where('orders.status', 'completed')
                ->whereBetween('orders.amount', [100, 1000])
                ->orderBy('orders.created_at', 'desc')
                ->limit(10)
                ->toSql(),
        ],
    ];
    
    return Response::success([
        'message' => '查询构建器示例（显示生成的 SQL）',
        'examples' => $examples,
    ]);
});

// GET /api/demo/query/aggregates - 聚合函数示例
$router->get('/demo/query/aggregates', function (\HolySword\Http\Request $request) {
    return Response::success([
        'message' => '聚合函数示例',
        'examples' => [
            'count' => [
                'title' => 'COUNT 统计',
                'code' => "db()->table('users')->where('status', 1)->count()",
                'description' => '返回符合条件的记录数',
            ],
            'sum' => [
                'title' => 'SUM 求和',
                'code' => "db()->table('orders')->sum('amount')",
                'description' => '返回指定字段的总和',
            ],
            'avg' => [
                'title' => 'AVG 平均值',
                'code' => "db()->table('products')->avg('price')",
                'description' => '返回指定字段的平均值',
            ],
            'max' => [
                'title' => 'MAX 最大值',
                'code' => "db()->table('products')->max('price')",
                'description' => '返回指定字段的最大值',
            ],
            'min' => [
                'title' => 'MIN 最小值',
                'code' => "db()->table('products')->min('price')",
                'description' => '返回指定字段的最小值',
            ],
        ],
    ]);
});

// GET /api/demo/query/crud - CRUD 操作示例
$router->get('/demo/query/crud', function (\HolySword\Http\Request $request) {
    return Response::success([
        'message' => 'CRUD 操作示例',
        'examples' => [
            'insert' => [
                'title' => '插入数据',
                'code' => "db()->table('users')->insert(['name' => 'John', 'email' => 'john@example.com'])",
                'description' => '返回插入的 ID',
            ],
            'insert_batch' => [
                'title' => '批量插入',
                'code' => "db()->table('users')->insertBatch([\n    ['name' => 'John', 'email' => 'john@example.com'],\n    ['name' => 'Jane', 'email' => 'jane@example.com'],\n])",
                'description' => '返回影响的行数',
            ],
            'update' => [
                'title' => '更新数据',
                'code' => "db()->table('users')->where('id', 1)->update(['name' => 'Jane'])",
                'description' => '返回影响的行数',
            ],
            'increment' => [
                'title' => '自增',
                'code' => "db()->table('products')->where('id', 1)->increment('stock', 10)",
                'description' => '将 stock 字段增加 10',
            ],
            'decrement' => [
                'title' => '自减',
                'code' => "db()->table('products')->where('id', 1)->decrement('stock', 5)",
                'description' => '将 stock 字段减少 5',
            ],
            'delete' => [
                'title' => '删除数据',
                'code' => "db()->table('users')->where('id', 1)->delete()",
                'description' => '返回影响的行数',
            ],
            'truncate' => [
                'title' => '清空表',
                'code' => "db()->table('logs')->truncate()",
                'description' => '清空表的所有数据',
            ],
        ],
    ]);
});

// GET /api/demo/query/helpers - 常用辅助方法
$router->get('/demo/query/helpers', function (\HolySword\Http\Request $request) {
    return Response::success([
        'message' => '常用辅助方法',
        'examples' => [
            'first' => [
                'title' => '获取第一条',
                'code' => "db()->table('users')->where('email', 'john@example.com')->first()",
            ],
            'find' => [
                'title' => '根据 ID 查找',
                'code' => "db()->table('users')->find(1)",
            ],
            'value' => [
                'title' => '获取单个字段值',
                'code' => "db()->table('users')->where('id', 1)->value('name')",
            ],
            'pluck' => [
                'title' => '获取某列所有值',
                'code' => "db()->table('users')->pluck('name')",
            ],
            'pluck_with_key' => [
                'title' => '获取键值对',
                'code' => "db()->table('users')->pluck('name', 'id')  // [id => name]",
            ],
            'exists' => [
                'title' => '检查是否存在',
                'code' => "db()->table('users')->where('email', 'john@example.com')->exists()",
            ],
            'doesnt_exist' => [
                'title' => '检查是否不存在',
                'code' => "db()->table('users')->where('email', 'john@example.com')->doesntExist()",
            ],
            'latest' => [
                'title' => '最新记录',
                'code' => "db()->table('posts')->latest()->first()",
            ],
            'oldest' => [
                'title' => '最旧记录',
                'code' => "db()->table('posts')->oldest()->first()",
            ],
            'distinct' => [
                'title' => '去重',
                'code' => "db()->table('orders')->select('user_id')->distinct()->get()",
            ],
        ],
    ]);
});