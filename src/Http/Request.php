<?php

/**
 * HolySword Framework - HTTP 请求处理
 * 
 * @package    HolySword
 * @subpackage Http
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Http;

/**
 * HTTP 请求类
 * 
 * 封装 HTTP 请求的所有信息，提供统一的访问接口。
 * 支持获取请求方法、路径、参数、请求头、Cookie、文件等。
 * 
 * 使用示例:
 * ```php
 * // 在控制器中
 * public function store(Request $request): Response
 * {
 *     $name = $request->input('name');
 *     $email = $request->input('email');
 *     $file = $request->file('avatar');
 *     
 *     if ($request->isAjax()) {
 *         // AJAX 请求处理
 *     }
 * }
 * ```
 * 
 * @package HolySword\Http
 */
class Request
{
    /**
     * HTTP 请求方法 (GET, POST, PUT, DELETE 等)
     * 
     * @var string
     */
    protected string $method;

    /**
     * 完整的请求 URI，包含查询字符串
     * 
     * @var string
     */
    protected string $uri;

    /**
     * 请求路径，不包含查询字符串
     * 
     * @var string
     */
    protected string $path;

    /**
     * URL 查询参数 ($_GET)
     * 
     * @var array
     */
    protected array $query;

    /**
     * POST 表单数据 ($_POST)
     * 
     * @var array
     */
    protected array $post;

    /**
     * 服务器环境变量 ($_SERVER)
     * 
     * @var array
     */
    protected array $server;

    /**
     * 解析后的 HTTP 请求头
     * 
     * @var array<string, string>
     */
    protected array $headers;

    /**
     * Cookie 数据 ($_COOKIE)
     * 
     * @var array
     */
    protected array $cookies;

    /**
     * 上传文件信息 ($_FILES)
     * 
     * @var array
     */
    protected array $files;

    /**
     * 原始请求体内容（用于 JSON 等非表单数据）
     * 
     * @var string|null
     */
    protected ?string $content = null;

    /**
     * 请求属性（用于中间件传递数据）
     * 
     * @var array
     */
    protected array $attributes = [];

    /**
     * 创建请求实例
     * 
     * 从 PHP 全局变量中初始化请求数据
     */
    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path = parse_url($this->uri, PHP_URL_PATH) ?: '/';
        $this->query = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->cookies = $_COOKIE;
        $this->files = $_FILES;
        $this->headers = $this->parseHeaders();
    }

    /**
     * 从全局变量捕获请求
     * 
     * 静态工厂方法，创建并返回新的请求实例
     * 
     * @return static 请求实例
     */
    public static function capture(): static
    {
        return new static();
    }

    /**
     * 从服务器变量中解析 HTTP 请求头
     * 
     * @return array 解析后的请求头数组
     */
    protected function parseHeaders(): array
    {
        $headers = [];

        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($name)] = $value;
            }
        }

        if (isset($this->server['CONTENT_TYPE'])) {
            $headers['content-type'] = $this->server['CONTENT_TYPE'];
        }

        if (isset($this->server['CONTENT_LENGTH'])) {
            $headers['content-length'] = $this->server['CONTENT_LENGTH'];
        }

        return $headers;
    }

    /**
     * 获取 HTTP 请求方法
     * 
     * @return string 大写的请求方法 (GET, POST, PUT, DELETE 等)
     */
    public function method(): string
    {
        return strtoupper($this->method);
    }

    /**
     * 获取完整的请求 URI
     * 
     * 包含查询字符串，如 /users?page=1
     * 
     * @return string 请求 URI
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * 获取请求路径
     * 
     * 不包含查询字符串，如 /users
     * 
     * @return string 请求路径
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * 判断请求方法是否为指定方法
     * 
     * @param string $method 要判断的 HTTP 方法
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    /**
     * 判断是否为 GET 请求
     * 
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * 判断是否为 POST 请求
     * 
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * 判断是否为 AJAX 请求
     * 
     * 通过检查 X-Requested-With 请求头判断
     * 
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->header('x-requested-with') === 'XMLHttpRequest';
    }

    /**
     * 判断客户端是否期望 JSON 响应
     * 
     * 通过检查 Accept 请求头判断
     * 
     * @return bool
     */
    public function expectsJson(): bool
    {
        return str_contains($this->header('accept', ''), 'application/json');
    }

    /**
     * 获取输入数据 (GET + POST + JSON 合并)
     * 
     * 自动检测 Content-Type，如果是 JSON 请求则自动解析 JSON 请求体
     * 
     * @param string|null $key 参数名，null 返回所有数据
     * @param mixed $default 默认值
     * @return mixed 参数值或所有数据
     * 
     * @example
     * ```php
     * $all = $request->input();          // 所有输入
     * $name = $request->input('name');   // 指定参数
     * $page = $request->input('page', 1); // 带默认值
     * ```
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        // 合并 GET 和 POST 数据
        $data = array_merge($this->query, $this->post);
        
        // 检查是否为 JSON 请求，如果是则合并 JSON 数据
        $contentType = $this->header('content-type', '');
        if (str_contains($contentType, 'application/json')) {
            $jsonData = $this->json();
            if (is_array($jsonData)) {
                $data = array_merge($data, $jsonData);
            }
        }

        if (is_null($key)) {
            return $data;
        }

        return $data[$key] ?? $default;
    }

    /**
     * 获取 URL 查询参数 (GET 参数)
     * 
     * @param string|null $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    /**
     * 获取 POST 表单数据
     * 
     * @param string|null $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function post(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->post;
        }

        return $this->post[$key] ?? $default;
    }

    /**
     * 获取 HTTP 请求头
     * 
     * @param string|null $key 请求头名称（不区分大小写）
     * @param mixed $default 默认值
     * @return mixed
     * 
     * @example
     * ```php
     * $token = $request->header('Authorization');
     * $type = $request->header('Content-Type');
     * ```
     */
    public function header(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->headers;
        }

        return $this->headers[strtolower($key)] ?? $default;
    }

    /**
     * 获取 Cookie 值
     * 
     * @param string|null $key Cookie 名称
     * @param mixed $default 默认值
     * @return mixed
     */
    public function cookie(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->cookies;
        }

        return $this->cookies[$key] ?? $default;
    }

    /**
     * 获取上传文件信息
     * 
     * @param string|null $key 文件字段名
     * @return mixed 文件信息数组或 null
     */
    public function file(?string $key = null): mixed
    {
        if (is_null($key)) {
            return $this->files;
        }

        return $this->files[$key] ?? null;
    }

    /**
     * 获取服务器环境变量
     * 
     * @param string|null $key 变量名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function server(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->server;
        }

        return $this->server[strtoupper($key)] ?? $default;
    }

    /**
     * 获取原始请求体内容
     * 
     * 用于获取 JSON 等非表单格式的请求数据
     * 
     * @return string 原始请求体
     */
    public function getContent(): string
    {
        if ($this->content === null) {
            $this->content = file_get_contents('php://input') ?: '';
        }

        return $this->content;
    }

    /**
     * 获取 JSON 格式的请求数据
     * 
     * 自动解析请求体中的 JSON 数据
     * 
     * @param string|null $key JSON 键名
     * @param mixed $default 默认值
     * @return mixed
     * 
     * @example
     * ```php
     * // 请求体: {"name": "John", "age": 25}
     * $data = $request->json();        // 所有数据
     * $name = $request->json('name');  // "John"
     * ```
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        $data = json_decode($this->getContent(), true) ?: [];

        if (is_null($key)) {
            return $data;
        }

        return $data[$key] ?? $default;
    }

    /**
     * 获取客户端 IP 地址
     * 
     * 支持代理转发的 X-Forwarded-For 等请求头
     * 
     * @return string IP 地址
     */
    public function ip(): string
    {
        $keys = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($keys as $key) {
            if (!empty($this->server[$key])) {
                $ip = $this->server[$key];
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    /**
     * 获取客户端 User-Agent 字符串
     * 
     * @return string User-Agent
     */
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * 判断指定输入是否存在
     * 
     * @param string $key 参数名
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->input());
    }

    /**
     * 获取所有输入数据
     * 
     * @return array 所有输入数据
     */
    public function all(): array
    {
        return $this->input();
    }

    /**
     * 只获取指定的输入
     * 
     * @param array $keys 要获取的参数名数组
     * @return array 指定的输入数据
     * 
     * @example
     * ```php
     * $data = $request->only(['name', 'email']);
     * ```
     */
    public function only(array $keys): array
    {
        $results = [];
        $input = $this->input();

        foreach ($keys as $key) {
            if (array_key_exists($key, $input)) {
                $results[$key] = $input[$key];
            }
        }

        return $results;
    }

    /**
     * 获取除指定键外的所有输入
     * 
     * @param array $keys 要排除的参数名数组
     * @return array 排除后的输入数据
     * 
     * @example
     * ```php
     * $data = $request->except(['password', 'token']);
     * ```
     */
    public function except(array $keys): array
    {
        $input = $this->input();

        foreach ($keys as $key) {
            unset($input[$key]);
        }

        return $input;
    }

    /**
     * 设置请求属性
     * 
     * 用于中间件向控制器传递数据
     * 
     * @param string $key 属性名
     * @param mixed $value 属性值
     * @return static
     * 
     * @example
     * ```php
     * // 在中间件中设置用户信息
     * $request->setAttribute('user_id', 123);
     * $request->setAttribute('user', $userInfo);
     * ```
     */
    public function setAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * 获取请求属性
     * 
     * 从中间件设置的属性中获取数据
     * 
     * @param string $key 属性名
     * @param mixed $default 默认值
     * @return mixed
     * 
     * @example
     * ```php
     * // 在控制器中获取用户信息
     * $userId = $request->getAttribute('user_id');
     * $user = $request->getAttribute('user');
     * ```
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * 获取所有请求属性
     * 
     * @return array 所有属性
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * 检查请求属性是否存在
     * 
     * @param string $key 属性名
     * @return bool
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }
}
