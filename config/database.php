<?php

/**
 * HolySword Framework - 数据库配置文件
 * 
 * 配置多个数据库连接，支持 MySQL、PostgreSQL、SQLite、SQL Server。
 * 所有敏感配置从 .env 文件读取。
 * 
 * 你可以通过 db() 辅助函数访问数据库：
 * ```php
 * $users = db()->table('users')->get();
 * $user = db()->table('users')->find(1);
 * ```
 * 
 * @package    HolySword
 * @subpackage Config
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

return [
    /*
    |--------------------------------------------------------------------------
    | 默认数据库连接
    |--------------------------------------------------------------------------
    |
    | 指定默认使用的数据库连接名称。
    |
    */
    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | 数据库连接配置
    |--------------------------------------------------------------------------
    |
    | 定义所有可用的数据库连接。可以配置多个连接，
    | 通过 db('connection_name') 切换使用不同的数据库。
    |
    */
    'connections' => [
        /*
        |--------------------------------------------------------------------------
        | MySQL 连接
        |--------------------------------------------------------------------------
        */
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'holysword'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => env('DB_PREFIX', ''),
            'options' => [
                \PDO::ATTR_PERSISTENT => false,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | MySQL 从库连接（示例）
        |--------------------------------------------------------------------------
        */
        'mysql_read' => [
            'driver' => 'mysql',
            'host' => env('DB_READ_HOST', env('DB_HOST', 'localhost')),
            'port' => env('DB_READ_PORT', env('DB_PORT', 3306)),
            'database' => env('DB_READ_DATABASE', env('DB_DATABASE', 'holysword')),
            'username' => env('DB_READ_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('DB_READ_PASSWORD', env('DB_PASSWORD', '')),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => env('DB_PREFIX', ''),
            'options' => [],
        ],

        /*
        |--------------------------------------------------------------------------
        | PostgreSQL 连接
        |--------------------------------------------------------------------------
        */
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('PGSQL_HOST', 'localhost'),
            'port' => env('PGSQL_PORT', 5432),
            'database' => env('PGSQL_DATABASE', 'holysword'),
            'username' => env('PGSQL_USERNAME', 'postgres'),
            'password' => env('PGSQL_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => env('PGSQL_PREFIX', ''),
            'schema' => 'public',
            'options' => [],
        ],

        /*
        |--------------------------------------------------------------------------
        | SQLite 连接
        |--------------------------------------------------------------------------
        */
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('SQLITE_DATABASE', base_path('database/database.sqlite')),
            'prefix' => '',
            'options' => [],
        ],

        /*
        |--------------------------------------------------------------------------
        | SQL Server 连接
        |--------------------------------------------------------------------------
        */
        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => env('SQLSRV_HOST', 'localhost'),
            'port' => env('SQLSRV_PORT', 1433),
            'database' => env('SQLSRV_DATABASE', 'holysword'),
            'username' => env('SQLSRV_USERNAME', 'sa'),
            'password' => env('SQLSRV_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'options' => [],
        ],
    ],
];
