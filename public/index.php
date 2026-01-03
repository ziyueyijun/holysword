<?php
/**
 * HolySword - 应用入口文件
 * 
 * 所有请求都通过此文件进入应用程序
 */

declare(strict_types=1);

// 定义应用根目录（上一级目录）
define('BASE_PATH', dirname(__DIR__));

// 加载 Composer 自动加载
require BASE_PATH . '/vendor/autoload.php';

// 加载辅助函数
require BASE_PATH . '/src/helpers.php';

// 创建应用实例并运行
$app = new HolySword\Foundation\Application(BASE_PATH);
$app->run();
