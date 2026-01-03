<?php

/**
 * HolySword Framework - 数据库连接管理器
 * 
 * 管理多个数据库连接，支持连接池和连接切换。
 * 提供统一的数据库连接获取接口。
 * 
 * @package    HolySword
 * @subpackage Database
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Database;

use PDO;
use PDOException;

/**
 * 数据库连接管理器
 * 
 * 管理多个数据库连接，支持连接池和连接切换。
 * 
 * 使用示例:
 * ```php
 * // 获取默认连接
 * $db = DatabaseManager::connection();
 * 
 * // 获取指定连接
 * $mysqlDb = DatabaseManager::connection('mysql');
 * $pgsqlDb = DatabaseManager::connection('pgsql');
 * 
 * // 查询
 * $users = DatabaseManager::connection('mysql')->query('SELECT * FROM users');
 * 
 * // 使用辅助函数
 * $db = db();           // 默认连接
 * $db = db('pgsql');    // PostgreSQL 连接
 * ```
 * 
 * @package HolySword\Database
 */
class DatabaseManager
{
    /**
     * 数据库连接池
     * 
     * 存储所有已创建的数据库连接实例
     * 
     * @var array<string, DB>
     */
    protected static array $connections = [];

    /**
     * 数据库配置
     * 
     * 存储所有数据库连接的配置信息
     * 
     * @var array
     */
    protected static array $config = [];

    /**
     * 默认连接名称
     * 
     * @var string
     */
    protected static string $defaultConnection = 'mysql';

    /**
     * 初始化配置
     * 
     * @param array $config 数据库配置数组
     * @return void
     */
    public static function configure(array $config): void
    {
        self::$config = $config;
        self::$defaultConnection = $config['default'] ?? 'mysql';
    }

    /**
     * 获取数据库连接
     * 
     * @param string|null $name 连接名称，null 使用默认连接
     * @return DB 数据库实例
     */
    public static function connection(?string $name = null): DB
    {
        $name = $name ?? self::$defaultConnection;

        // 如果连接已存在，直接返回
        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        // 获取连接配置
        $config = self::getConnectionConfig($name);

        // 创建新连接
        self::$connections[$name] = new DB($config);

        return self::$connections[$name];
    }

    /**
     * 获取连接配置
     * 
     * @param string $name 连接名称
     * @return array 连接配置数组
     * @throws PDOException 当配置不存在时抛出
     */
    protected static function getConnectionConfig(string $name): array
    {
        // 优先从配置文件获取
        if (!empty(self::$config['connections'][$name])) {
            return self::$config['connections'][$name];
        }

        // 尝试从配置文件加载
        if (function_exists('config')) {
            $config = config("database.connections.{$name}");
            if ($config) {
                return $config;
            }
        }

        throw new PDOException("数据库连接配置不存在: {$name}");
    }

    /**
     * 设置默认连接
     * 
     * @param string $name 连接名称
     * @return void
     */
    public static function setDefaultConnection(string $name): void
    {
        self::$defaultConnection = $name;
    }

    /**
     * 获取默认连接名称
     * 
     * @return string 默认连接名称
     */
    public static function getDefaultConnection(): string
    {
        return self::$defaultConnection;
    }

    /**
     * 检查连接是否存在
     * 
     * @param string $name 连接名称
     * @return bool 连接是否存在
     */
    public static function hasConnection(string $name): bool
    {
        return isset(self::$connections[$name]);
    }

    /**
     * 获取所有活动连接名称
     * 
     * @return array 连接名称数组
     */
    public static function getConnections(): array
    {
        return array_keys(self::$connections);
    }

    /**
     * 断开指定连接
     * 
     * @param string|null $name 连接名称，null 表示默认连接
     * @return void
     */
    public static function disconnect(?string $name = null): void
    {
        $name = $name ?? self::$defaultConnection;

        if (isset(self::$connections[$name])) {
            self::$connections[$name]->disconnect();
            unset(self::$connections[$name]);
        }
    }

    /**
     * 断开所有连接
     * 
     * @return void
     */
    public static function disconnectAll(): void
    {
        foreach (self::$connections as $connection) {
            $connection->disconnect();
        }
        self::$connections = [];
    }

    /**
     * 重新连接
     * 
     * @param string|null $name 连接名称，null 表示默认连接
     * @return DB 数据库实例
     */
    public static function reconnect(?string $name = null): DB
    {
        self::disconnect($name);
        return self::connection($name);
    }

    /**
     * 在指定连接上执行回调
     * 
     * @param string $name 连接名称
     * @param callable $callback 要执行的回调函数
     * @return mixed 回调函数的返回值
     */
    public static function using(string $name, callable $callback): mixed
    {
        $connection = self::connection($name);
        return $callback($connection);
    }

    /**
     * 动态添加连接配置
     * 
     * @param string $name 连接名称
     * @param array $config 连接配置
     * @return void
     */
    public static function addConnection(string $name, array $config): void
    {
        if (!isset(self::$config['connections'])) {
            self::$config['connections'] = [];
        }
        self::$config['connections'][$name] = $config;
    }

    /**
     * 魔术方法：静态调用默认连接的方法
     * 
     * @param string $method 方法名
     * @param array $arguments 方法参数
     * @return mixed 方法返回值
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return self::connection()->$method(...$arguments);
    }
}
