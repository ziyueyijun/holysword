<?php

/**
 * HolySword Framework - 日志系统
 * 
 * 提供 PSR-3 风格的日志记录功能，支持多种日志级别。
 * 日志文件按日期分割，支持自动清理过期日志。
 * 
 * @package    HolySword
 * @subpackage Log
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Log;

/**
 * 日志记录器
 * 
 * 支持 8 种日志级别：emergency, alert, critical, error, warning, notice, info, debug
 * 
 * 使用示例:
 * ```php
 * $logger = new Logger(storage_path('logs'));
 * 
 * $logger->info('用户登录成功', ['user_id' => 1]);
 * $logger->error('数据库连接失败', ['host' => 'localhost']);
 * $logger->debug('调试信息', ['data' => $someData]);
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
     */
    public function __construct(string $logPath)
    {
        $this->logPath = rtrim($logPath, '/\\');
        
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
        $timestamp = date($this->dateFormat);
        $level = strtoupper($level);
        
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
     * @return string 完整的日志文件路径
     */
    protected function getLogFilename(): string
    {
        return $this->logPath . DIRECTORY_SEPARATOR . date($this->fileFormat) . '.log';
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
     * 清理过期日志文件
     * 
     * @param int $days 保留天数，默认 30 天
     * @return int 删除的文件数量
     */
    public function clean(int $days = 30): int
    {
        $count = 0;
        $threshold = time() - ($days * 86400);
        
        $files = glob($this->logPath . DIRECTORY_SEPARATOR . '*.log');
        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
                $count++;
            }
        }
        
        return $count;
    }
}
