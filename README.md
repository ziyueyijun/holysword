# 🗡️ HolySword Framework

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.1+-blue.svg" alt="PHP Version">
  <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License">
  <img src="https://img.shields.io/badge/Build-Stable-brightgreen.svg" alt="Build Status">
</p>

一个基于 PHP 8.1 原生开发的轻量级全栈框架，完全自主研发，不依赖第三方开源项目。

## 🌟 特性

- **原生 PHP 8.1** - 使用最新 PHP 特性，无第三方依赖
- **依赖注入容器** - 强大的 IoC 容器，支持自动依赖解析
- **路由系统** - 灵活的路由定义，支持参数、分组、中间件
- **HTTP 抽象** - 完整的请求/响应封装
- **配置管理** - 简洁的配置加载和访问
- **MVC 架构** - 清晰的代码组织结构
- **数据库支持** - 多连接数据库支持和查询构建器
- **数据验证** - 强大的数据验证功能
- **日志系统** - PSR-3 风格的日志记录
- **中间件系统** - 灵活的中间件机制

## ⚙️ 系统要求

- PHP >= 8.1
- Composer
- 支持 URL 重写的 Web 服务器（Apache/Nginx）

## 🚀 快速开始

### 第一步：安装框架

```bash
# 克隆项目
git clone https://github.com/ziyueyijun/holysword.git

# 进入目录
cd holysword

# 安装依赖
composer install

# 复制环境配置
cp .env.example .env
```

### 第二步：配置 Web 服务器

将 Web 服务器根目录指向 `public/` 目录。

**Apache 配置示例：**
```apache
<VirtualHost *:80>
    DocumentRoot "/path/to/holysword/public"
    ServerName localhost
    
    <Directory "/path/to/holysword/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx 配置示例：**
```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/holysword/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 第三步：配置环境变量

编辑 `.env` 文件，配置数据库和其他环境变量：

```env
APP_NAME=HolySword
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=holysword
DB_USERNAME=root
DB_PASSWORD=
```

### 第四步：访问测试

访问 `http://localhost/` 应该看到欢迎信息。

## 📁 目录结构

```
HolySword/
├── app/                          # 应用代码目录
│   ├── Controllers/              # 控制器
│   │   ├── Controller.php        # 控制器基类
│   │   └── HomeController.php    # 示例控制器
│   └── Middleware/               # 中间件
│       ├── AuthMiddleware.php    # 认证中间件
│       ├── CorsMiddleware.php    # CORS中间件
│       └── LogMiddleware.php     # 日志中间件
├── config/                       # 配置文件目录
│   ├── app.php                   # 应用配置
│   └── database.php              # 数据库配置
├── public/                       # 公共目录（Web 根目录）
│   ├── .htaccess                 # Apache 重写规则
│   ├── index.php                 # 应用入口文件
│   └── index.html                # 框架官网介绍页面
├── routes/                       # 路由定义目录
│   ├── web.php                   # Web 路由
│   └── api.php                   # API 路由（自动添加 /api 前缀）
├── src/                          # 框架核心代码
│   ├── Config/                   # 配置管理
│   │   └── Config.php
│   ├── Container/                # 依赖注入容器
│   │   └── Container.php
│   ├── Database/                 # 数据库组件
│   │   ├── DB.php                # 数据库操作类
│   │   ├── DatabaseManager.php   # 数据库连接管理器
│   │   └── QueryBuilder.php      # 查询构建器
│   ├── Exception/                # 异常处理
│   │   └── ExceptionHandler.php  # 异常处理器
│   ├── Foundation/               # 应用基础
│   │   └── Application.php
│   ├── Http/                     # HTTP 处理
│   │   ├── Request.php           # HTTP 请求
│   │   └── Response.php          # HTTP 响应
│   ├── Log/                      # 日志系统
│   │   └── Logger.php            # 日志记录器
│   ├── Middleware/               # 中间件接口
│   │   └── MiddlewareInterface.php
│   ├── Routing/                  # 路由系统
│   │   ├── Router.php            # 路由器
│   │   └── Route.php             # 路由实例
│   ├── Validation/               # 数据验证
│   │   └── Validator.php         # 数据验证器
│   └── helpers.php               # 全局辅助函数
├── storage/                      # 存储目录
│   ├── cache/                    # 缓存文件
│   └── logs/                     # 日志文件
├── composer.json                 # Composer 配置
└── .env.example                  # 环境配置示例
```

## 🛠️ 核心功能详解

### 路由系统

在 `routes/web.php` 和 `routes/api.php` 中定义路由：

```php
<?php

use HolySword\Http\Response;

// 基础路由
$router->get('/', function () {
    return Response::json(['message' => 'Hello World']);
});

// 带参数的路由
$router->get('/users/{id}', function ($request, $id) {
    return Response::success(['user_id' => $id]);
});

// 使用控制器
$router->get('/home', [App\Controllers\HomeController::class, 'index']);

// POST 路由
$router->post('/users', [App\Controllers\UserController::class, 'store']);

// 其他 HTTP 方法
$router->put('/users/{id}', [App\Controllers\UserController::class, 'update']);
$router->delete('/users/{id}', [App\Controllers\UserController::class, 'destroy']);

// 多方法路由
$router->match(['get', 'post'], '/form', function () {
    return 'GET or POST';
});

// 任意方法
$router->any('/any', function () {
    return 'Any method';
});
```

#### 路由分组

```php
// 带前缀的分组
$router->group(['prefix' => 'admin'], function ($router) {
    $router->get('/dashboard', [AdminController::class, 'dashboard']);
    $router->get('/users', [AdminController::class, 'users']);
});
// 访问路径: /admin/dashboard, /admin/users

// 带中间件的分组
$router->group(['prefix' => 'api', 'middleware' => 'auth'], function ($router) {
    $router->get('/profile', [UserController::class, 'profile']);
});
```

#### 路由中间件

```php
// 为单个路由添加中间件
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware('auth');

// 多个中间件
$router->get('/admin/settings', [AdminController::class, 'settings'])
    ->middleware(['auth', 'admin']);
```

#### 命名路由

```php
$router->get('/users/{id}', [UserController::class, 'show'])
    ->name('users.show');
```

### 控制器

在 `app/Controllers/` 目录创建控制器：

```php
<?php

namespace App\Controllers;

use HolySword\Http\Request;
use HolySword\Http\Response;

class UserController extends Controller
{
    /**
     * 用户列表
     */
    public function index(Request $request): Response
    {
        $users = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];
        
        return $this->success($users);
    }

    /**
     * 显示单个用户
     */
    public function show(Request $request, int $id): Response
    {
        return $this->success([
            'id' => $id,
            'name' => 'User ' . $id,
        ]);
    }

    /**
     * 创建用户
     */
    public function store(Request $request): Response
    {
        $name = $request->input('name');
        $email = $request->input('email');
        
        // 验证和保存逻辑...
        
        return $this->success(['id' => 1, 'name' => $name]);
    }

    /**
     * 更新用户
     */
    public function update(Request $request, int $id): Response
    {
        $data = $request->only(['name', 'email']);
        
        return $this->success(['id' => $id, ...$data]);
    }

    /**
     * 删除用户
     */
    public function destroy(Request $request, int $id): Response
    {
        return $this->success(null, '删除成功');
    }
}
```

### Request 请求对象

```php
// 获取请求方法
$method = $request->method();  // GET, POST, PUT, DELETE...

// 获取请求路径
$path = $request->path();      // /users/1

// 获取请求 URI（包含查询字符串）
$uri = $request->uri();        // /users/1?page=2

// 判断请求方法
if ($request->isGet()) { }
if ($request->isPost()) { }
if ($request->isMethod('PUT')) { }

// 判断是否为 AJAX 请求
if ($request->isAjax()) { }

// 判断是否期望 JSON 响应
if ($request->expectsJson()) { }

// 获取输入数据（GET + POST）
$all = $request->input();              // 所有输入
$name = $request->input('name');       // 指定键
$name = $request->input('name', '默认值'); // 带默认值

// 获取 GET 参数
$page = $request->query('page', 1);

// 获取 POST 数据
$email = $request->post('email');

// 获取 JSON 请求体
$data = $request->json();
$name = $request->json('name');

// 获取请求头
$token = $request->header('Authorization');
$contentType = $request->header('Content-Type');

// 获取 Cookie
$token = $request->cookie('session_token');

// 获取上传文件
$file = $request->file('avatar');

// 获取客户端 IP
$ip = $request->ip();

// 获取 User Agent
$ua = $request->userAgent();

// 判断输入是否存在
if ($request->has('email')) { }

// 获取指定的输入
$data = $request->only(['name', 'email']);

// 获取除指定外的输入
$data = $request->except(['password', 'token']);

// 获取所有输入
$all = $request->all();
```

### Response 响应对象

```php
use HolySword\Http\Response;

// 基础响应
return Response::make('Hello World');
return Response::make('<h1>Hello</h1>', 200, ['Content-Type' => 'text/html']);

// JSON 响应
return Response::json(['name' => 'John', 'age' => 25]);
return Response::json($data, 201);  // 自定义状态码

// 成功响应（统一格式）
return Response::success($data);
return Response::success($data, '操作成功');
// 输出: {"code": 0, "message": "success", "data": ...}

// 错误响应（统一格式）
return Response::error('参数错误');
return Response::error('未找到', 404, 404);
// 输出: {"code": 1, "message": "参数错误", "data": null}

// 重定向
return Response::redirect('/login');
return Response::redirect('/dashboard', 301);  // 永久重定向

// 链式设置
return Response::make('OK')
    ->setStatusCode(201)
    ->header('X-Custom', 'value')
    ->withHeaders([
        'X-Header-1' => 'value1',
        'X-Header-2' => 'value2',
    ]);

// 设置 Cookie
return Response::success($data)
    ->cookie('token', 'abc123', 60);  // 60分钟过期
```

### 配置管理

配置文件位于 `config/` 目录，返回 PHP 数组：

```php
// config/app.php
return [
    'name' => 'HolySword',
    'env' => 'local',
    'debug' => true,
    'url' => 'http://localhost',
    'timezone' => 'Asia/Shanghai',
];

// config/database.php
return [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'database' => 'holysword',
    'username' => 'root',
    'password' => '',
];
```

获取配置：

```php
// 使用辅助函数
$appName = config('app.name');
$debug = config('app.debug', false);  // 带默认值

// 使用 Config 对象
$config = app('config');
$driver = $config->get('database.driver');

// 设置配置
$config->set('app.debug', false);

// 判断配置是否存在
if ($config->has('database.host')) { }
```

### 依赖注入容器

```php
use HolySword\Container\Container;

// 获取容器实例
$container = Container::getInstance();
// 或使用辅助函数
$container = app();

// 绑定服务
$container->bind('cache', FileCache::class);
$container->bind(CacheInterface::class, RedisCache::class);

// 绑定闭包
$container->bind('logger', function ($container) {
    return new Logger($container->make('config')->get('logging'));
});

// 绑定单例
$container->singleton('db', function ($container) {
    return new Database($container->make('config')->get('database'));
});

// 绑定已存在的实例
$container->instance('request', $request);

// 注册别名
$container->alias(CacheInterface::class, 'cache');

// 解析服务
$cache = $container->make('cache');
$cache = $container->make(CacheInterface::class);
$cache = app('cache');  // 辅助函数

// 带参数解析
$logger = $container->make(Logger::class, ['level' => 'debug']);
```

### 数据库支持

框架支持多种数据库（MySQL、PostgreSQL、SQLite、SQL Server）和多连接配置：

```php
// 基础查询
$users = db()->query('SELECT * FROM users WHERE status = ?', [1]);

// 使用指定连接
$data = db('mysql')->query('SELECT * FROM orders');
$pgData = db('pgsql')->query('SELECT * FROM products');

// 插入
$id = db()->insert('users', ['name' => 'John', 'email' => 'john@example.com']);

// 更新
$affected = db()->update('users', ['name' => 'Jane'], ['id' => 1]);

// 删除
$affected = db()->delete('users', ['id' => 1]);
```

#### 查询构建器

框架提供了强大的查询构建器，支持链式调用：

```php
// 基础查询
$users = db()->table('users')->where('status', 1)->get();

// LEFT JOIN
$orders = db()->table('orders')
    ->select('orders.*', 'users.name as user_name')
    ->leftJoin('users', 'orders.user_id', '=', 'users.id')
    ->where('orders.status', 'completed')
    ->orderBy('orders.created_at', 'desc')
    ->limit(10)
    ->get();

// 聚合函数
$count = db()->table('users')->where('status', 1)->count();
$total = db()->table('orders')->sum('amount');

// 复杂查询示例
$results = db()->table('orders')
    ->select('orders.id', 'orders.amount', 'users.name', 'products.title')
    ->leftJoin('users', 'orders.user_id', '=', 'users.id')
    ->leftJoin('products', 'orders.product_id', '=', 'products.id')
    ->where('orders.status', 'completed')
    ->whereBetween('orders.amount', [100, 1000])
    ->orderBy('orders.created_at', 'desc')
    ->limit(10)
    ->get();
```

#### 查询构建器功能

1. **SELECT 操作**
   - `select()` - 指定查询字段
   - `distinct()` - 去重查询
   - `addSelect()` - 添加更多字段

2. **JOIN 操作**
   - `join()` - INNER JOIN
   - `leftJoin()` - LEFT JOIN
   - `rightJoin()` - RIGHT JOIN
   - `crossJoin()` - CROSS JOIN

3. **WHERE 条件**
   - `where()` - 基础条件
   - `orWhere()` - OR 条件
   - `whereIn()` - IN 条件
   - `whereNotIn()` - NOT IN 条件
   - `whereBetween()` - BETWEEN 条件
   - `whereNull()` - IS NULL 条件
   - `whereNotNull()` - IS NOT NULL 条件

4. **排序和分组**
   - `orderBy()` - 排序
   - `groupBy()` - 分组
   - `having()` - HAVING 条件

5. **分页**
   - `limit()` - 限制数量
   - `offset()` - 偏移量
   - `forPage()` - 分页查询

6. **聚合函数**
   - `count()` - 统计数量
   - `sum()` - 求和
   - `avg()` - 平均值
   - `max()` - 最大值
   - `min()` - 最小值

7. **CRUD 操作**
   - `insert()` - 插入数据
   - `update()` - 更新数据
   - `delete()` - 删除数据
   - `increment()` - 自增字段
   - `decrement()` - 自减字段

### 数据验证

框架提供了强大的数据验证功能：

```php
use HolySword\Validation\Validator;

$validator = Validator::make($request->all(), [
    'name' => 'required|min:2|max:50',
    'email' => 'required|email',
    'age' => 'numeric|min:1|max:120',
]);

if ($validator->fails()) {
    return Response::error($validator->firstError(), 422, 422);
}
```

支持的验证规则：
- `required` - 必填
- `email` - 邮箱格式
- `numeric` - 数字
- `integer` - 整数
- `string` - 字符串
- `array` - 数组
- `min:n` - 最小值/长度
- `max:n` - 最大值/长度
- `between:min,max` - 范围
- `in:a,b,c` - 枚举值
- `url` - URL 格式
- `ip` - IP 地址
- `date` - 日期格式
- `confirmed` - 确认字段
- `boolean` - 布尔值

### 日志系统

框架提供了 PSR-3 风格的日志记录功能：

```php
$logger = logger();

// 记录不同级别的日志
$logger->info('用户登录成功', ['user_id' => 1]);
$logger->error('数据库连接失败', ['host' => 'localhost']);
$logger->debug('调试信息', ['data' => $someData]);
```

### 中间件

创建中间件：

```php
<?php

namespace App\Middleware;

use Closure;
use HolySword\Http\Request;
use HolySword\Http\Response;
use HolySword\Middleware\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization');
        
        if (!$token) {
            return Response::error('未授权', 401, 401);
        }
        
        // 验证 token...
        
        return $next($request);
    }
}
```

注册中间件别名：

```php
// 在 routes/web.php 或引导文件中
$router->aliasMiddleware('auth', App\Middleware\AuthMiddleware::class);
$router->aliasMiddleware('cors', App\Middleware\CorsMiddleware::class);
```

### 辅助函数

框架提供了一系列全局辅助函数：

```php
// 获取应用实例或解析服务
$app = app();
$cache = app('cache');

// 获取配置
$debug = config('app.debug');
$name = config('app.name', 'Default');

// 创建响应
$response = response('OK');
$json = json(['status' => 'ok']);
$redirect = redirect('/login');

// 获取路径
$root = base_path();
$app = app_path('Controllers');
$config = config_path('app.php');
$public = public_path('assets');
$storage = storage_path('logs/app.log');

// 获取环境变量
$debug = env('APP_DEBUG', false);

// 数据库操作
$db = db();           // 默认连接
$db = db('pgsql');    // PostgreSQL 连接

// 日志记录
$logger = logger();

// 调试函数
dump($variable);      // 打印变量
dd($var1, $var2);     // 打印并终止
```

## 🌐 API 路由

API 路由定义在 `routes/api.php` 中，会自动添加 `/api` 前缀：

```php
<?php

use HolySword\Http\Response;

// 访问路径: /api/status
$router->get('/status', function () {
    return Response::success([
        'status' => 'running',
        'timestamp' => time(),
    ]);
});

// 访问路径: /api/users
$router->get('/users', [App\Controllers\Api\UserController::class, 'index']);
```

## 🧪 功能测试

框架提供了完整的功能测试路由，在 `routes/api.php` 中定义了大量测试端点：

### 请求处理测试
- `GET /api/demo/request` - 获取请求基本信息
- `GET /api/demo/query?name=John&age=25` - 获取 Query 参数
- `POST /api/demo/post` - 获取 POST 数据
- `POST /api/demo/json` - 获取 JSON 请求体
- `GET /api/demo/headers` - 获取请求头

### 路由参数测试
- `GET /api/demo/users/{id}` - 单个路由参数
- `GET /api/demo/posts/{postId}/comments/{commentId}` - 多个路由参数

### RESTful CRUD 测试
- `POST /api/demo/resources` - 创建资源
- `PUT /api/demo/resources/{id}` - 更新资源
- `DELETE /api/demo/resources/{id}` - 删除资源

### 响应功能测试
- `GET /api/demo/error?type=404` - 错误响应示例
- `GET /api/demo/redirect?url=/api/status` - 重定向示例
- `GET /api/demo/cookie?name=test&value=123` - 设置 Cookie

### 中间件测试
- `GET /api/demo/with-middleware` - 日志中间件示例

### 数据库测试
- `GET /api/demo/db/connections` - 查看数据库连接配置
- `GET /api/demo/db/test` - 测试数据库连接
- `GET /api/demo/query/examples` - 查询构建器示例

### 数据验证测试
- `POST /api/demo/validate` - 数据验证示例

访问 `http://localhost/test.html` 可以在浏览器中测试这些功能。

## ❌ 错误处理

框架会自动捕获异常并根据 `app.debug` 配置返回不同级别的错误信息：

**调试模式开启时（开发环境）：**
```json
{
    "code": 500,
    "message": "Error message",
    "file": "/path/to/file.php",
    "line": 42,
    "trace": "..."
}
```

**调试模式关闭时（生产环境）：**
```json
{
    "code": 500,
    "message": "服务器内部错误"
}
```

## 🌍 环境配置

复制 `.env.example` 为 `.env` 并根据环境修改：

```env
APP_NAME=HolySword
APP_ENV=local          # local, production, testing
APP_DEBUG=true
APP_URL=http://localhost
APP_KEY=

APP_TIMEZONE=Asia/Shanghai
APP_LOCALE=zh_CN

# 数据库配置
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=holysword
DB_USERNAME=root
DB_PASSWORD=
```

## 📄 许可证

MIT License

## 📦 版本

- **v1.0.0** - 初始版本
  - 依赖注入容器
  - 路由系统
  - HTTP 请求/响应
  - 配置管理
  - 中间件支持
  - 数据库支持
  - 数据验证
  - 日志系统