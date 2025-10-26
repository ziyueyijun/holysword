<?php

use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

$whoops = new Run;
$handler = new PrettyPageHandler;

// 添加自定义调试信息
$handler->addDataTable('HTTP 请求', [
    'Method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    'URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    'Headers' => function_exists('getallheaders') ? getallheaders() : [],
    'GET Data' => $_GET,
    'POST Data' => $_POST,
    'Files' => $_FILES,
    'Cookies' => $_COOKIE,
    'Session' => $_SESSION ?? [],
    'Server/Env' => $_SERVER,
]);

$whoops->pushHandler($handler);
$whoops->register();