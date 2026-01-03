<?php

/**
 * HolySword Framework - HTTP 响应处理
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
 * HTTP 响应类
 * 
 * 封装 HTTP 响应的所有内容，包括状态码、响应头和响应体。
 * 提供多种便捷方法创建 JSON、重定向等常见响应。
 * 
 * 使用示例:
 * ```php
 * // JSON 响应
 * return Response::json(['name' => 'John', 'age' => 25]);
 * 
 * // 成功响应（统一格式）
 * return Response::success($data, '操作成功');
 * 
 * // 错误响应
 * return Response::error('参数错误', 400, 400);
 * 
 * // 重定向
 * return Response::redirect('/login');
 * ```
 * 
 * @package HolySword\Http
 */
class Response
{
    /**
     * 响应体内容
     * 
     * @var string
     */
    protected string $content = '';

    /**
     * HTTP 状态码
     * 
     * @var int
     */
    protected int $statusCode = 200;

    /**
     * HTTP 响应头
     * 
     * @var array<string, string>
     */
    protected array $headers = [];

    /**
     * HTTP 状态码对应的文本描述
     * 
     * @var array<int, string>
     */
    protected static array $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    /**
     * 创建响应实例
     * 
     * @param string $content 响应内容
     * @param int $status HTTP 状态码
     * @param array $headers 响应头
     */
    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $status;
        $this->headers = $headers;
    }

    /**
     * 静态工厂方法创建响应实例
     * 
     * @param string $content 响应内容
     * @param int $status HTTP 状态码
     * @param array $headers 响应头
     * @return static 响应实例
     */
    public static function make(string $content = '', int $status = 200, array $headers = []): static
    {
        return new static($content, $status, $headers);
    }

    /**
     * 创建 JSON 格式响应
     * 
     * 自动设置 Content-Type 为 application/json
     * 
     * @param mixed $data 要编码为 JSON 的数据
     * @param int $status HTTP 状态码
     * @param array $headers 额外的响应头
     * @return static 响应实例
     * 
     * @example
     * ```php
     * return Response::json(['name' => 'John', 'age' => 25]);
     * return Response::json($data, 201); // 创建成功
     * ```
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        
        return new static(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $status,
            $headers
        );
    }

    /**
     * 创建统一格式的成功响应
     * 
     * 返回格式: {"code": 0, "message": "success", "data": ...}
     * 
     * @param mixed $data 响应数据
     * @param string $message 成功消息
     * @return static 响应实例
     * 
     * @example
     * ```php
     * return Response::success(['id' => 1, 'name' => 'John']);
     * return Response::success($data, '创建成功');
     * ```
     */
    public static function success(mixed $data = null, string $message = 'success'): static
    {
        return static::json([
            'code' => 0,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * 创建统一格式的错误响应
     * 
     * 返回格式: {"code": 1, "message": "error", "data": null}
     * 调试模式下可显示详细错误信息，生产环境隐藏敏感信息
     * 
     * @param string $message 错误消息
     * @param int $code 业务错误码
     * @param int $status HTTP 状态码
     * @param \Throwable|null $exception 异常对象（可选，用于调试模式显示详细信息）
     * @return static 响应实例
     * 
     * @example
     * ```php
     * return Response::error('参数错误');
     * return Response::error('未找到', 404, 404);
     * return Response::error('操作失败', 500, 500, $e); // 传入异常对象
     * ```
     */
    public static function error(string $message = 'error', int $code = 1, int $status = 400, ?\Throwable $exception = null): static
    {
        $isDebug = function_exists('env') ? env('APP_DEBUG', false) : false;
        
        $response = [
            'code' => $code,
            'message' => $message,
            'data' => null,
        ];
        
        // 调试模式下显示详细错误信息
        if ($isDebug && $exception !== null) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => array_slice(
                    array_map(function ($item) {
                        return [
                            'file' => $item['file'] ?? 'unknown',
                            'line' => $item['line'] ?? 0,
                            'function' => ($item['class'] ?? '') . ($item['type'] ?? '') . ($item['function'] ?? ''),
                        ];
                    }, $exception->getTrace()),
                    0,
                    10
                ),
            ];
        }
        
        return static::json($response, $status);
    }

    /**
     * 创建带分页信息的成功响应
     * 
     * 返回格式: {"code": 0, "message": "success", "data": [...], "meta": {"pagination": {...}}}
     * 
     * @param array $data 列表数据
     * @param array $pagination 分页信息
     * @param string $message 成功消息
     * @return static 响应实例
     * 
     * @example
     * ```php
     * return Response::paginate($users, [
     *     'total' => 100,
     *     'per_page' => 20,
     *     'current_page' => 1,
     *     'last_page' => 5,
     * ]);
     * ```
     */
    public static function paginate(array $data, array $pagination, string $message = 'success', array $extra = []): static
    {
        $response = [
            'code' => 0,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'pagination' => $pagination,
            ],
        ];
        
        // 添加额外数据（如 all 字段）
        if (!empty($extra)) {
            $response = array_merge($response, $extra);
        }
        
        return static::json($response);
    }

    /**
     * 创建带验证错误的响应
     * 
     * 返回格式: {"code": 1001, "message": "参数验证失败", "data": null, "errors": {...}}
     * 
     * @param string $message 错误消息
     * @param array $errors 验证错误详情
     * @param int $code 业务错误码
     * @return static 响应实例
     */
    public static function validationError(string $message, array $errors = [], int $code = 1001): static
    {
        return static::json([
            'code' => $code,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], 422);
    }

    /**
     * 创建 HTTP 重定向响应
     * 
     * @param string $url 重定向目标 URL
     * @param int $status HTTP 状态码 (302 临时重定向, 301 永久重定向)
     * @return static 响应实例
     * 
     * @example
     * ```php
     * return Response::redirect('/login');
     * return Response::redirect('/new-page', 301); // 永久重定向
     * ```
     */
    public static function redirect(string $url, int $status = 302): static
    {
        return new static('', $status, ['Location' => $url]);
    }

    /**
     * 设置响应体内容
     * 
     * @param string $content 响应内容
     * @return static 支持链式调用
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * 获取响应体内容
     * 
     * @return string 响应内容
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * 设置 HTTP 状态码
     * 
     * @param int $code 状态码
     * @return static 支持链式调用
     */
    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;

        return $this;
    }

    /**
     * 获取 HTTP 状态码
     * 
     * @return int 状态码
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * 设置单个响应头
     * 
     * @param string $name 响应头名称
     * @param string $value 响应头值
     * @return static 支持链式调用
     */
    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * 批量设置响应头
     * 
     * @param array $headers 响应头数组
     * @return static 支持链式调用
     */
    public function withHeaders(array $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }

        return $this;
    }

    /**
     * 获取所有响应头
     * 
     * @return array 响应头数组
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * 设置 Cookie
     * 
     * @param string $name Cookie 名称
     * @param string $value Cookie 值
     * @param int $minutes 过期时间（分钟），0 表示会话 Cookie
     * @param string $path Cookie 路径
     * @param string $domain Cookie 域名
     * @param bool $secure 是否仅 HTTPS 传输
     * @param bool $httpOnly 是否禁止 JavaScript 访问
     * @return static 支持链式调用
     * 
     * @example
     * ```php
     * return Response::success($data)
     *     ->cookie('token', 'abc123', 60); // 60分钟过期
     * ```
     */
    public function cookie(
        string $name,
        string $value,
        int $minutes = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true
    ): static {
        $expire = $minutes > 0 ? time() + ($minutes * 60) : 0;
        
        setcookie($name, $value, [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => 'Lax',
        ]);

        return $this;
    }

    /**
     * 发送 HTTP 响应头
     * 
     * @return static
     */
    protected function sendHeaders(): static
    {
        if (headers_sent()) {
            return $this;
        }

        // 发送状态码
        $statusText = static::$statusTexts[$this->statusCode] ?? 'unknown status';
        header(sprintf('HTTP/1.1 %d %s', $this->statusCode, $statusText), true, $this->statusCode);

        // 发送自定义头
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}", true);
        }

        return $this;
    }

    /**
     * 发送响应体内容
     * 
     * @return static
     */
    protected function sendContent(): static
    {
        echo $this->content;

        return $this;
    }

    /**
     * 发送完整的 HTTP 响应
     * 
     * 包括响应头和响应体，这是响应的最终输出方法
     * 
     * @return static
     */
    public function send(): static
    {
        $this->sendHeaders();
        $this->sendContent();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        return $this;
    }

    /**
     * 将响应转换为字符串
     * 
     * @return string 响应内容
     */
    public function __toString(): string
    {
        return $this->content;
    }
}
