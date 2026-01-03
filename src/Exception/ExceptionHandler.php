<?php

/**
 * HolySword Framework - 异常处理器
 * 
 * 统一处理应用中的异常和错误，提供友好的错误响应。
 * 支持调试模式显示详细信息，生产环境隐藏敏感信息。
 * 
 * @package    HolySword
 * @subpackage Exception
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Exception;

use HolySword\Http\Response;
use Throwable;

/**
 * 异常处理器
 * 
 * 统一处理应用中的异常，提供友好的错误响应。
 * 调试模式下显示详细信息，生产环境隐藏敏感信息。
 * 
 * 使用示例:
 * ```php
 * $handler = new ExceptionHandler(true); // debug = true
 * $response = $handler->handle($exception);
 * $response->send();
 * ```
 */
class ExceptionHandler
{
    /**
     * 是否开启调试模式
     * 
     * @var bool
     */
    protected bool $debug;

    /**
     * 日志文件存储路径
     * 
     * @var string|null
     */
    protected ?string $logPath;

    /**
     * 创建异常处理器实例
     * 
     * @param bool $debug 是否开启调试模式
     * @param string|null $logPath 日志存储路径
     */
    public function __construct(bool $debug = false, ?string $logPath = null)
    {
        $this->debug = $debug;
        $this->logPath = $logPath;
    }

    /**
     * 处理异常并返回响应
     * 
     * @param Throwable $e 异常对象
     * @return Response HTTP 响应
     */
    public function handle(Throwable $e): Response
    {
        // 记录异常日志
        $this->log($e);

        // 获取 HTTP 状态码
        $statusCode = $this->getStatusCode($e);

        // 调试模式返回详细信息
        if ($this->debug) {
            return $this->renderDebugResponse($e, $statusCode);
        }

        // 生产环境返回简洁信息
        return $this->renderProductionResponse($e, $statusCode);
    }

    /**
     * 获取异常对应的 HTTP 状态码
     * 
     * @param Throwable $e 异常对象
     * @return int HTTP 状态码
     */
    protected function getStatusCode(Throwable $e): int
    {
        // 如果异常有 getStatusCode 方法（如 HttpException）
        if (method_exists($e, 'getStatusCode')) {
            /** @var callable $callback */
            $callback = [$e, 'getStatusCode'];
            return (int) call_user_func($callback);
        }

        // 根据异常类型判断
        $code = $e->getCode();
        if ($code >= 400 && $code < 600) {
            return $code;
        }

        return 500;
    }

    /**
     * 调试模式响应
     * 
     * 返回包含详细异常信息的响应
     * 
     * @param Throwable $e 异常对象
     * @param int $statusCode HTTP 状态码
     * @return Response HTTP 响应
     */
    protected function renderDebugResponse(Throwable $e, int $statusCode): Response
    {
        return Response::json([
            'code' => $statusCode,
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $this->formatTrace($e->getTrace()),
        ], $statusCode);
    }

    /**
     * 生产环境响应
     * 
     * 返回简洁的错误信息，隐藏敏感细节
     * 
     * @param Throwable $e 异常对象
     * @param int $statusCode HTTP 状态码
     * @return Response HTTP 响应
     */
    protected function renderProductionResponse(Throwable $e, int $statusCode): Response
    {
        $messages = [
            400 => '请求参数错误',
            401 => '未授权访问',
            403 => '禁止访问',
            404 => '资源未找到',
            405 => '请求方法不允许',
            422 => '数据验证失败',
            429 => '请求过于频繁',
            500 => '服务器内部错误',
            502 => '网关错误',
            503 => '服务暂时不可用',
        ];

        $message = $messages[$statusCode] ?? '发生错误';

        return Response::json([
            'code' => $statusCode,
            'message' => $message,
            'data' => null,
        ], $statusCode);
    }

    /**
     * 格式化堆栈跟踪
     * 
     * @param array $trace 原始堆栈跟踪数组
     * @return array 格式化后的堆栈信息
     */
    protected function formatTrace(array $trace): array
    {
        return array_map(function ($item) {
            return [
                'file' => $item['file'] ?? 'unknown',
                'line' => $item['line'] ?? 0,
                'function' => ($item['class'] ?? '') . ($item['type'] ?? '') . ($item['function'] ?? ''),
            ];
        }, array_slice($trace, 0, 10));
    }

    /**
     * 记录异常日志
     * 
     * @param Throwable $e 异常对象
     * @return void
     */
    protected function log(Throwable $e): void
    {
        // 使用统一的日志系统，写入 logs/ 目录
        logger('app')->error('Exception: ' . get_class($e), [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * 注册全局异常处理
     * 
     * 设置全局异常处理器和错误处理器
     * 
     * @return void
     */
    public function register(): void
    {
        set_exception_handler(function (Throwable $e) {
            $response = $this->handle($e);
            $response->send();
        });

        set_error_handler(function ($level, $message, $file, $line) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        });
    }
}
