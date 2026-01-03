<?php

/**
 * HolySword Framework - 日志系统
 * 
 * 提供 PSR-3 风格的日志记录功能，支持多种日志级别。
 * 
 * 日志优化策略：
 * 1. 所有日志只写入到 logs 文件夹
 * 2. 仅记录重要错误信息，忽略频繁无意义日志
 * 3. 自动过滤敏感信息（密码、密钥等）
 * 4. 不同功能日志按通道/年月日组织
 * 5. APP_DEBUG=false 时不记录任何日志
 * 
 * @package    HolySword
 * @subpackage Log
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    2.0.0
 */

declare(strict_types=1);

namespace HolySword\Log;

/**
 * 日志记录器
 * 
 * 支持 8 种日志级别：emergency, alert, critical, error, warning, notice, info, debug
 * 生产环境下（APP_DEBUG=false）不记录任何日志
 * 
 * 使用示例:
 * ```php
 * $logger = new Logger(base_path('logs'), 'payment');
 * 
 * $logger->error('支付失败', ['order_id' => 123]);
 * $logger->critical('系统异常', ['error' => $message]);
 * 
 * // 清理 30 天前的日志
 * $logger->clean(30);
 * ```
 * 
 * @package HolySword\Log
 */
class Logger
{
    /**
     * 敏感字段列表（需要过滤的字段名）
     */
    private const SENSITIVE_KEYS = [
        'password', 'passwd', 'pwd', 'secret', 'token', 'api_key', 'apikey',
        'access_token', 'refresh_token', 'private_key', 'credential',
        'db_password', 'database_password', 'mysql_password'
    ];

    /**
     * 是否启用日志（根据 APP_DEBUG 决定）
     */
    protected static ?bool $enabled = null;

    /**
     * 仅记录的日志级别（重要级别，忽略 info/debug 等频繁日志）
     */
    private const IMPORTANT_LEVELS = [
        self::EMERGENCY,
        self::ALERT, 
        self::CRITICAL,
        self::ERROR,
        self::WARNING,
    ];

    /**
     * 日志级别：紧急
     */
    public const EMERGENCY = 'emergency';

    /**
     * 日志级别：警报
     */
    public const ALERT     = 'alert';

    /**
     * 日志级别：严重
     */
    public const CRITICAL  = 'critical';

    /**
     * 日志级别：错误
     */
    public const ERROR     = 'error';

    /**
     * 日志级别：警告
     */
    public const WARNING   = 'warning';

    /**
     * 日志级别：通知
     */
    public const NOTICE    = 'notice';

    /**
     * 日志级别：信息
     */
    public const INFO      = 'info';

    /**
     * 日志级别：调试
     */
    public const DEBUG     = 'debug';

    /**
     * 日志文件存储路径
     * 
     * @var string
     */
    protected string $logPath;

    /**
     * 日志通道名称（用于区分不同功能模块）
     * 
     * @var string
     */
    protected string $channel = 'app';

    /**
     * 日志时间格式
     * 
     * @var string
     */
    protected string $dateFormat = 'Y-m-d H:i:s';

    /**
     * 日志文件名格式
     * 
     * @var string
     */
    protected string $fileFormat = 'Y-m-d';

    /**
     * 创建日志记录器实例
     * 
     * @param string $logPath 日志文件存储路径
     * @param string $channel 日志通道名称（用于区分功能模块）
     */
    public function __construct(string $logPath, string $channel = 'app')
    {
        $this->logPath = rtrim($logPath, '/\\');
        $this->channel = $channel;
        
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * 记录紧急级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return void
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * 记录警报级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return void
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * 记录严重级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * 记录错误级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * 记录警告级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * 记录通知级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return void
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * 记录信息级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * 记录调试级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * 记录日志
     * 
     * @param string $level 日志级别
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void
    {
        // 检查日志是否启用（APP_DEBUG=false时不记录）
        if (!self::isEnabled()) {
            return;
        }
        
        // 只记录重要级别的日志，忽略info/debug/notice等频繁日志
        if (!in_array($level, self::IMPORTANT_LEVELS)) {
            return;
        }
        
        $timestamp = date($this->dateFormat);
        $level = strtoupper($level);
        
        // 过滤敏感信息
        $context = $this->filterSensitiveData($context);
        
        // 插值上下文到消息
        $message = $this->interpolate($message, $context);
        
        // 格式化日志行
        $logLine = sprintf(
            "[%s] %s: %s%s\n",
            $timestamp,
            $level,
            $message,
            !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        
        // 写入日志文件
        $filename = $this->getLogFilename();
        file_put_contents($filename, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * 检查日志是否启用
     * 
     * @return bool
     */
    public static function isEnabled(): bool
    {
        if (self::$enabled === null) {
            // 从 .env 文件读取 APP_DEBUG 配置
            if (function_exists('env')) {
                self::$enabled = (bool) env('APP_DEBUG', true);
            } else {
                // 直接读取环境变量
                $debug = getenv('APP_DEBUG');
                self::$enabled = $debug === false || $debug === 'true' || $debug === '1' || $debug === true;
            }
        }
        return self::$enabled;
    }
    
    /**
     * 过滤敏感数据
     * 
     * @param array $data 原始数据
     * @return array 过滤后的数据
     */
    protected function filterSensitiveData(array $data): array
    {
        $filtered = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            
            // 检查是否为敏感字段
            $isSensitive = false;
            foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
                if (str_contains($lowerKey, $sensitiveKey)) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                $filtered[$key] = '***FILTERED***';
            } elseif (is_array($value)) {
                $filtered[$key] = $this->filterSensitiveData($value);
            } else {
                $filtered[$key] = $value;
            }
        }
        return $filtered;
    }

    /**
     * 插值上下文到消息
     * 
     * @param string $message 原始消息
     * @param array $context 上下文数据
     * @return string 插值后的消息
     */
    protected function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_string($val) || is_numeric($val)) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        return strtr($message, $replace);
    }

    /**
     * 获取日志文件名
     * 
     * 目录结构: logs/通道名/年月日.log
     * 例如: logs/payment/2025-12-29.log
     * 
     * @return string 完整的日志文件路径
     */
    protected function getLogFilename(): string
    {
        // 创建通道目录: logs/通道名/
        $channelDir = $this->logPath . DIRECTORY_SEPARATOR . $this->channel;
        if (!is_dir($channelDir)) {
            mkdir($channelDir, 0755, true);
        }
        
        // 格式: logs/通道名/YYYY-MM-DD.log
        return $channelDir . DIRECTORY_SEPARATOR . date($this->fileFormat) . '.log';
    }

    /**
     * 设置日志时间格式
     * 
     * @param string $format 时间格式字符串
     * @return self
     */
    public function setDateFormat(string $format): self
    {
        $this->dateFormat = $format;
        return $this;
    }

    /**
     * 设置日志文件名格式
     * 
     * @param string $format 文件名格式字符串
     * @return self
     */
    public function setFileFormat(string $format): self
    {
        $this->fileFormat = $format;
        return $this;
    }

    /**
     * 设置日志通道名称
     * 
     * @param string $channel 通道名称
     * @return self
     */
    public function setChannel(string $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * 获取当前日志通道名称
     * 
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * 清理过期日志文件
     * 
     * @param int $days 保留天数，默认 30 天
     * @return int 删除的文件数量
     */
    public function clean(int $days = 30): int
    {
        $count = 0;
        $threshold = time() - ($days * 86400);
        
        // 遍历所有通道目录
        $channelDirs = glob($this->logPath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
        foreach ($channelDirs as $channelDir) {
            $files = glob($channelDir . DIRECTORY_SEPARATOR . '*.log');
            foreach ($files as $file) {
                if (filemtime($file) < $threshold) {
                    @unlink($file);
                    $count++;
                }
            }
            // 如果目录为空，删除目录
            if (count(glob($channelDir . DIRECTORY_SEPARATOR . '*')) === 0) {
                @rmdir($channelDir);
            }
        }
        
        // 同时清理根目录下的旧格式日志（兼容吆理）
        $oldFiles = glob($this->logPath . DIRECTORY_SEPARATOR . '*.log');
        foreach ($oldFiles as $file) {
            if (filemtime($file) < $threshold) {
                @unlink($file);
                $count++;
            }
        }
        
        return $count;
    }
}
