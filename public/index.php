<?php

/**
 * HolySword Framework - 应用入口文件
 * 
 * 该文件是应用程序的唯一入口点，所有 HTTP 请求都将被定向到此文件。
 * 它负责：
 * - 检查 PHP 版本兼容性（要求 PHP 8.1+）
 * - 加载 Composer 自动加载器
 * - 初始化应用实例
 * - 处理请求并发送响应
 * 
 * 请确保你的 Web 服务器将所有请求重写到此文件。
 * 参考同目录下的 .htaccess 文件获取 Apache 配置示例。
 * 
 * @package    HolySword
 * @subpackage Public
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

use HolySword\Foundation\Application;

// 检查 PHP 版本是否满足最低要求
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    die('HolySword 框架需要 PHP 8.1 或更高版本');
}

// 加载 Composer 自动加载
require __DIR__ . '/../vendor/autoload.php';

// 创建应用实例
$app = new Application(dirname(__DIR__));

// 运行应用
$app->run();
