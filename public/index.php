<?php

require_once '../vendor/autoload.php';

//设置中国时区
date_default_timezone_set('PRC');
//处理跨域问题
header("Access-Control-Allow-Origin:*");
//定义根目录常量
define('ROOT_PATH', dirname(__DIR__));
//初始化框架
\HolySword\main::init();