<?php

/**
 * HolySword Framework - 数据库操作类
 * 
 * 基于 PDO 的数据库操作封装，提供统一的数据库访问接口。
 * 支持 MySQL、PostgreSQL、SQLite、SQL Server 等多种数据库。
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
use PDOStatement;
use HolySword\Database\Grammar\Grammar;
use HolySword\Database\Grammar\MySqlGrammar;
use HolySword\Database\Grammar\PostgresGrammar;
use HolySword\Database\Grammar\SqliteGrammar;
use HolySword\Database\Grammar\SqlServerGrammar;

/**
 * 数据库操作类
 * 
 * 基于 PDO 的简单数据库操作封装，支持 MySQL、PostgreSQL、SQLite、SQL Server。
 * 配置从 config/database.php 和 .env 文件读取。
 * 
 * 使用示例:
 * ```php
 * // 通过辅助函数使用默认连接
 * $users = db()->query('SELECT * FROM users WHERE status = ?', [1]);
 * 
 * // 使用指定连接
 * $data = db('mysql')->query('SELECT * FROM orders');
 * $pgData = db('pgsql')->query('SELECT * FROM products');
 * 
 * // 插入
 * $id = db()->insert('users', ['name' => 'John', 'email' => 'john@example.com']);
 * 
 * // 更新
 * $affected = db()->update('users', ['name' => 'Jane'], ['id' => 1]);
 * 
 * // 删除
 * $affected = db()->delete('users', ['id' => 1]);
 * ```
 * 
 * @package HolySword\Database
 */
class DB
{
    /**
     * PDO 数据库连接实例
     * 
     * @var PDO|null
     */
    protected ?PDO $pdo = null;

    /**
     * 数据库配置数组
     * 
     * @var array
     */
    protected array $config;

    /**
     * 最后执行的 PDO 语句
     * 
     * @var PDOStatement|null
     */
    protected ?PDOStatement $statement = null;

    /**
     * SQL 语法生成器
     * 
     * @var Grammar|null
     */
    protected ?Grammar $grammar = null;

    /**
     * 构造函数
     * 
     * @param array $config 数据库配置，会与默认配置合并
     */
    public function __construct(array $config = [])
    {
        // 默认配置，优先从 env 读取
        $defaults = [
            'driver' => function_exists('env') ? env('DB_CONNECTION', 'mysql') : 'mysql',
            'host' => function_exists('env') ? env('DB_HOST', 'localhost') : 'localhost',
            'port' => function_exists('env') ? (int) env('DB_PORT', 3306) : 3306,
            'database' => function_exists('env') ? env('DB_DATABASE', '') : '',
            'username' => function_exists('env') ? env('DB_USERNAME', 'root') : 'root',
            'password' => function_exists('env') ? env('DB_PASSWORD', '') : '',
            'charset' => function_exists('env') ? env('DB_CHARSET', 'utf8mb4') : 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => function_exists('env') ? env('DB_PREFIX', '') : '',
            'options' => [],
        ];
        
        $this->config = array_merge($defaults, $config);
    }

    /**
     * 获取 PDO 连接实例
     * 
     * 如果连接不存在则自动创建
     * 
     * @return PDO 数据库连接实例
     */
    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }

    /**
     * 获取 SQL 语法生成器
     * 
     * 根据数据库驱动返回对应的 Grammar 实例
     * 
     * @return Grammar SQL 语法生成器
     */
    public function getGrammar(): Grammar
    {
        if ($this->grammar === null) {
            $this->grammar = $this->createGrammar();
        }
        return $this->grammar;
    }

    /**
     * 创建 SQL 语法生成器
     * 
     * @return Grammar
     */
    protected function createGrammar(): Grammar
    {
        return match ($this->config['driver']) {
            'mysql' => new MySqlGrammar(),
            'pgsql' => new PostgresGrammar(),
            'sqlite' => new SqliteGrammar(),
            'sqlsrv' => new SqlServerGrammar(),
            default => new MySqlGrammar(),
        };
    }

    /**
     * 获取数据库驱动类型
     * 
     * @return string
     */
    public function getDriverName(): string
    {
        return $this->config['driver'];
    }

    /**
     * 建立数据库连接
     * 
     * 根据配置的驱动类型创建对应的 PDO 连接
     * 
     * @return void
     * @throws PDOException 当数据库连接失败或驱动不支持时抛出
     */
    protected function connect(): void
    {
        $driver = $this->config['driver'];
        
        // 记录连接尝试（调试模式）
        $this->logDatabaseConnection('attempting', [
            'driver' => $driver,
            'host' => $this->config['host'],
            'port' => $this->config['port'],
            'database' => $this->config['database'],
            'username' => $this->config['username'],
            'charset' => $this->config['charset'] ?? 'N/A',
        ]);
        
        switch ($driver) {
            case 'mysql':
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['database'],
                    $this->config['charset']
                );
                break;
            case 'pgsql':
                $dsn = sprintf(
                    'pgsql:host=%s;port=%d;dbname=%s',
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['database']
                );
                break;
            case 'sqlite':
                $dsn = sprintf('sqlite:%s', $this->config['database']);
                break;
            case 'sqlsrv':
                $dsn = sprintf(
                    'sqlsrv:Server=%s,%d;Database=%s',
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['database']
                );
                break;
            default:
                $error = "不支持的数据库驱动: {$driver}";
                $this->logDatabaseConnection('failed', ['error' => $error]);
                throw new PDOException($error);
        }

        $options = array_merge([
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ], $this->config['options']);

        try {
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $options
            );
            
            // 记录连接成功
            $this->logDatabaseConnection('success', [
                'dsn' => $this->maskDsn($dsn),
                'options' => array_keys($options),
            ]);
            
        } catch (PDOException $e) {
            // 记录连接失败
            $this->logDatabaseConnection('failed', [
                'dsn' => $this->maskDsn($dsn),
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        }
    }
    
    /**
     * 记录数据库连接信息
     * 
     * @param string $status 连接状态：attempting, success, failed
     * @param array $info 连接信息
     * @return void
     */
    protected function logDatabaseConnection(string $status, array $info = []): void
    {
        // 检查是否启用调试模式
        $debugEnabled = false;
        
        if (function_exists('env')) {
            $debugEnabled = env('APP_DEBUG', false);
        } elseif (isset($_ENV['APP_DEBUG'])) {
            $debugEnabled = $_ENV['APP_DEBUG'] === 'true' || $_ENV['APP_DEBUG'] === true;
        }
        
        // 只在调试模式下记录
        if (!$debugEnabled) {
            return;
        }
        
        // 使用统一的日志系统，写入 logs/ 目录
        if (function_exists('logger')) {
            logger('app')->debug('Database debug: ' . strtoupper($status), $info);
        }
    }
    
    /**
     * 隐藏 DSN 中的敏感信息
     * 
     * @param string $dsn 原始 DSN
     * @return string 隐藏后的 DSN
     */
    protected function maskDsn(string $dsn): string
    {
        // 保留 DSN 结构，但不暴露具体值
        return $dsn;
    }

    /**
     * 执行 SQL 查询并返回所有结果
     * 
     * @param string $sql SQL 查询语句
     * @param array $bindings 绑定参数数组
     * @return array 查询结果数组
     */
    public function query(string $sql, array $bindings = []): array
    {
        $this->statement = $this->getConnection()->prepare($sql);
        $this->statement->execute($bindings);
        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 执行 SQL 查询并返回第一行
     * 
     * @param string $sql SQL 查询语句
     * @param array $bindings 绑定参数数组
     * @return array|null 第一行结果或 null
     */
    public function first(string $sql, array $bindings = []): ?array
    {
        $this->statement = $this->getConnection()->prepare($sql);
        $this->statement->execute($bindings);
        $result = $this->statement->fetch();
        return $result ?: null;
    }

    /**
     * 执行 SQL 并返回影响的行数
     * 
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数数组
     * @return int 影响的行数
     */
    public function execute(string $sql, array $bindings = []): int
    {
        $this->statement = $this->getConnection()->prepare($sql);
        $this->statement->execute($bindings);
        return $this->statement->rowCount();
    }

    /**
     * 插入数据
     * 
     * @param string $table 表名
     * @param array $data 要插入的数据数组
     * @return int 插入记录的 ID
     */
    public function insert(string $table, array $data): int
    {
        $grammar = $this->getGrammar();
        $table = $grammar->wrapTable($this->config['prefix'] . $table);
        
        $columns = array_map(fn($col) => $grammar->wrapColumn($col), array_keys($data));
        $columnsStr = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columnsStr}) VALUES ({$placeholders})";
        
        $this->execute($sql, array_values($data));
        
        return (int) $this->getConnection()->lastInsertId();
    }

    /**
     * 批量插入数据
     * 
     * @param string $table 表名
     * @param array $rows 要插入的数据数组（二维数组）
     * @return int 影响的行数
     */
    public function insertBatch(string $table, array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $grammar = $this->getGrammar();
        $table = $grammar->wrapTable($this->config['prefix'] . $table);
        $columns = array_keys($rows[0]);
        $wrappedColumns = array_map(fn($col) => $grammar->wrapColumn($col), $columns);
        $columnStr = implode(', ', $wrappedColumns);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($rows), $placeholders));

        $sql = "INSERT INTO {$table} ({$columnStr}) VALUES {$allPlaceholders}";
        
        $bindings = [];
        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $bindings[] = $row[$col] ?? null;
            }
        }

        return $this->execute($sql, $bindings);
    }

    /**
     * 更新数据
     * 
     * @param string $table 表名
     * @param array $data 要更新的数据
     * @param array $where WHERE 条件
     * @return int 影响的行数
     */
    public function update(string $table, array $data, array $where): int
    {
        $grammar = $this->getGrammar();
        $table = $grammar->wrapTable($this->config['prefix'] . $table);
        
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = $grammar->wrapColumn($column) . " = ?";
        }
        $setStr = implode(', ', $setParts);

        $whereParts = [];
        foreach (array_keys($where) as $column) {
            $whereParts[] = $grammar->wrapColumn($column) . " = ?";
        }
        $whereStr = implode(' AND ', $whereParts);

        $sql = "UPDATE {$table} SET {$setStr} WHERE {$whereStr}";
        
        $bindings = array_merge(array_values($data), array_values($where));
        
        return $this->execute($sql, $bindings);
    }

    /**
     * 删除数据
     * 
     * @param string $table 表名
     * @param array $where WHERE 条件
     * @return int 影响的行数
     */
    public function delete(string $table, array $where): int
    {
        $grammar = $this->getGrammar();
        $table = $grammar->wrapTable($this->config['prefix'] . $table);
        
        $whereParts = [];
        foreach (array_keys($where) as $column) {
            $whereParts[] = $grammar->wrapColumn($column) . " = ?";
        }
        $whereStr = implode(' AND ', $whereParts);

        $sql = "DELETE FROM {$table} WHERE {$whereStr}";
        
        return $this->execute($sql, array_values($where));
    }

    /**
     * 开始事务
     * 
     * @return bool 是否成功
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * 提交事务
     * 
     * @return bool 是否成功
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    /**
     * 回滚事务
     * 
     * @return bool 是否成功
     */
    public function rollback(): bool
    {
        return $this->getConnection()->rollBack();
    }

    /**
     * 在事务中执行回调
     * 
     * 自动处理事务的开始、提交和回滚
     * 
     * @param callable $callback 要执行的回调函数
     * @return mixed 回调函数的返回值
     * @throws \Throwable 当回调执行失败时抛出
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 获取最后插入的 ID
     * 
     * @return int 最后插入的记录 ID
     */
    public function lastInsertId(): int
    {
        return (int) $this->getConnection()->lastInsertId();
    }

    /**
     * 获取表前缀
     * 
     * @return string 配置的表前缀
     */
    public function getPrefix(): string
    {
        return $this->config['prefix'];
    }

    /**
     * 关闭数据库连接
     * 
     * @return void
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }

    /*
    |--------------------------------------------------------------------------
    | 查询构建器
    |--------------------------------------------------------------------------
    */

    /**
     * 开始链式查询
     * 
     * @param string $table 表名
     * @return QueryBuilder
     * 
     * @example
     * ```php
     * // 基础查询
     * $users = db()->table('users')->where('status', 1)->get();
     * 
     * // 复杂查询
     * $orders = db()->table('orders')
     *     ->select('orders.*', 'users.name as user_name')
     *     ->leftJoin('users', 'orders.user_id', '=', 'users.id')
     *     ->where('orders.status', 'completed')
     *     ->orderBy('orders.created_at', 'desc')
     *     ->limit(10)
     *     ->get();
     * 
     * // 聚合函数
     * $count = db()->table('users')->where('status', 1)->count();
     * $total = db()->table('orders')->sum('amount');
     * ```
     */
    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    /**
     * 原始 SQL 查询（返回 QueryBuilder）
     * 
     * @param string $sql 原始 SQL
     * @param array $bindings 绑定参数
     * @return array
     */
    public function raw(string $sql, array $bindings = []): array
    {
        return $this->query($sql, $bindings);
    }

    /**
     * 获取表的查询构建器（别名）
     * 
     * @param string $table 表名
     * @return QueryBuilder 查询构建器实例
     */
    public function from(string $table): QueryBuilder
    {
        return $this->table($table);
    }
}
