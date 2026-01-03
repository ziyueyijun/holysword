# HolySword Framework

一个轻量级的 PHP 8.1+ 原生框架，专注于简洁、高效和易用性。

## 特性

- **轻量级**：核心代码精简，无冗余依赖
- **原生 PHP**：基于 PHP 8.1+ 原生特性构建
- **完整的 ORM**：支持 MySQL、PostgreSQL、SQLite、SQL Server
- **优雅的路由**：支持 RESTful API 和 Web 路由
- **依赖注入**：内置服务容器
- **中间件支持**：灵活的请求/响应处理
- **模型关联**：支持一对一、一对多、多对多关联

## 环境要求

- PHP >= 8.1
- MySQL >= 5.7 / PostgreSQL >= 10 / SQLite 3
- Composer

## 快速开始

### 1. 安装

```bash
# 克隆项目
git clone https://github.com/holysword/framework.git holysword

# 进入目录
cd holysword

# 安装依赖
composer install

# 复制环境配置
cp .env.example .env

# 配置数据库连接
# 编辑 .env 文件，设置数据库信息
```

### 2. 配置 Web 服务器

#### Nginx 配置

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/holysword/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### Apache 配置

确保 `public/.htaccess` 文件存在：

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>
```

### 3. 测试安装

访问 `http://your-domain.com/api/status`，应返回：

```json
{
    "code": 0,
    "message": "success",
    "data": {
        "status": "running",
        "timestamp": 1234567890,
        "app": "HolySword"
    }
}
```

## 基本使用

### 路由定义

```php
// routes/api.php

// 基础路由
$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'store']);
$router->get('/users/{id}', [UserController::class, 'show']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);

// 闭包路由
$router->get('/hello', function () {
    return Response::success(['message' => 'Hello World']);
});
```

### 控制器

```php
// app/Controllers/UserController.php

namespace App\Controllers;

use HolySword\Http\Response;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return Response::success($users);
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return Response::error('用户不存在', 404);
        }
        return Response::success($user);
    }
}
```

### 模型

```php
// app/Models/User.php

namespace App\Models;

use HolySword\Database\Model\Model;

class User extends Model
{
    protected string $table = 'users';
    
    protected array $fillable = [
        'name', 'email', 'password'
    ];
    
    protected array $hidden = [
        'password'
    ];
}
```

### 数据库操作

```php
// 查询所有
$users = User::all();

// 条件查询
$users = User::where('status', 1)->get();

// 查找单条
$user = User::find(1);
$user = User::where('email', 'test@example.com')->first();

// 创建
$user = User::create([
    'name' => '张三',
    'email' => 'zhangsan@example.com'
]);

// 更新
$user->update(['name' => '李四']);

// 删除
$user->delete();
```

## 目录结构

```
holysword/
├── app/                    # 应用代码
│   ├── Controllers/        # 控制器
│   ├── Middleware/         # 中间件
│   ├── Models/             # 模型
│   └── Services/           # 服务层
├── config/                 # 配置文件
│   ├── app.php             # 应用配置
│   └── database.php        # 数据库配置
├── docs/                   # 文档
├── example/                # 示例代码
├── public/                 # Web 入口
│   └── index.php           # 入口文件
├── routes/                 # 路由定义
│   ├── api.php             # API 路由
│   └── web.php             # Web 路由
├── src/                    # 框架核心
├── tests/                  # 测试文件
├── .env.example            # 环境配置示例
├── .gitignore              # Git 忽略文件
├── composer.json           # Composer 配置
└── README.md               # 项目说明
```

## 文档

详细文档请参阅 `docs/` 目录：

- [安装部署](docs/安装部署.md)
- [路由使用](docs/路由使用.md)
- [数据库操作](docs/数据库操作.md)

## 许可证

本项目采用 [MIT 许可证](LICENSE)。

## 贡献

欢迎提交 Issue 和 Pull Request。
