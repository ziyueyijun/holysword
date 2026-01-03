<?php

/**
 * HolySword Framework - 数据库连接基类
 * 
 * 提供数据库连接的抽象基类，支持多种数据库驱动。
 * 
 * @package    HolySword
 * @subpackage Database\Connection
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Database\Connection;

use PDO;
use PDOException;
use PDOStatement;
use HolySword\Database\Grammar\Grammar;

/**
 * 数据库连接基类
 * 
 * 封装 PDO 连接，提供统一的数据库操作接口。
 * 子类实现特定数据库的连接和配置。
 * 
 * @package HolySword\Database\Connection
 */
abstract class Connection
{
    /**
     * PDO 实例
     * 
     * @var PDO|null
     */
    protected ?PDO $pdo = null;

    /**
     * 连接配置
     * 
     * @var array
     */
    protected array $config = [];

    /**
     * SQL 语法实例
     * 
     * @var Grammar|null
     */
    protected ?Grammar $grammar = null;

    /**
     * 表前缀
     * 
     * @var string
     */
    protected string $tablePrefix = '';

    /**
     * 查询日志
     * 
     * @var array
     */
    protected array $queryLog = [];

    /**
     * 是否记录查询日志
     * 
     * @var bool
     */
    protected bool $loggingQueries = false;

    /**
     * 重连次数
     * 
     * @var int
     */
    protected int $reconnectAttempts = 0;

    /**
     * 最大重连次数
     * 
     * @var int
     */
    protected int $maxReconnectAttempts = 3;

    /**
     * 创建连接实例
     * 
     * @param array $config 连接配置
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->tablePrefix = $config['prefix'] ?? '';
        $this->grammar = $this->createGrammar();
        $this->grammar->setTablePrefix($this->tablePrefix);
    }

    /**
     * 创建对应的 Grammar 实例
     * 
     * @return Grammar
     */
    abstract protected function createGrammar(): Grammar;

    /**
     * 获取 PDO DSN 字符串
     * 
     * @return string
     */
    abstract protected function getDsn(): string;

    /**
     * 获取默认 PDO 选项
     * 
     * @return array
     */
    protected function getDefaultOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    /**
     * 连接数据库
     * 
     * @return PDO
     * @throws PDOException
     */
    public function connect(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $dsn = $this->getDsn();
        $username = $this->config['username'] ?? null;
        $password = $this->config['password'] ?? null;
        $options = array_merge(
            $this->getDefaultOptions(),
            $this->config['options'] ?? []
        );

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
            $this->configureConnection();
            $this->reconnectAttempts = 0;
        } catch (PDOException $e) {
            throw new PDOException(
                "数据库连接失败: " . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        return $this->pdo;
    }

    /**
     * 配置连接（设置字符集等）
     * 
     * 子类可重写此方法进行特定配置
     * 
     * @return void
     */
    protected function configureConnection(): void
    {
        // 子类实现
    }

    /**
     * 获取 PDO 实例
     * 
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo ?? $this->connect();
    }

    /**
     * 获取 Grammar 实例
     * 
     * @return Grammar
     */
    public function getGrammar(): Grammar
    {
        return $this->grammar;
    }

    /**
     * 获取表前缀
     * 
     * @return string
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * 执行查询并返回结果
     * 
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数
     * @return array 查询结果
     */
    public function select(string $sql, array $bindings = []): array
    {
        return $this->run($sql, $bindings, function ($sql, $bindings) {
            $statement = $this->getPdo()->prepare($sql);
            $this->bindValues($statement, $bindings);
            $statement->execute();
            return $statement->fetchAll();
        });
    }

    /**
     * 执行查询并返回第一条结果
     * 
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数
     * @return array|null 单条结果或 null
     */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $results = $this->select($sql, $bindings);
        return $results[0] ?? null;
    }

    /**
     * 执行 INSERT 语句
     * 
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数
     * @return int 插入的记录 ID
     */
    public function insert(string $sql, array $bindings = []): int
    {
        return $this->run($sql, $bindings, function ($sql, $bindings) {
            $statement = $this->getPdo()->prepare($sql);
            $this->bindValues($statement, $bindings);
            $statement->execute();
            
            return $this->getLastInsertId();
        });
    }

    /**
     * 获取最后插入的 ID
     * 
     * @return int
     */
    protected function getLastInsertId(): int
    {
        $query = $this->grammar->getLastInsertIdQuery();
        
        if ($query !== null) {
            // 某些数据库需要执行查询获取
            $result = $this->getPdo()->query($query)->fetch();
            return (int) ($result['id'] ?? 0);
        }
        
        return (int) $this->getPdo()->lastInsertId();
    }

    /**
     * 执行 UPDATE 语句
     * 
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数
     * @return int 影响的行数
     */
    public function update(string $sql, array $bindings = []): int
    {
        return $this->affectingStatement($sql, $bindings);
    }

    /**
     * 执行 DELETE 语句
     * 
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数
     * @return int 影响的行数
     */
    public function delete(string $sql, array $bindings = []): int
    {
        return $this->affectingStatement($sql, $bindings);
    }

    /**
     * 执行影响行数的语句
     * 
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数
     * @return int 影响的行数
     */
    public function affectingStatement(string $sql, array $bindings = []): int
    {
        return $this->run($sql, $bindings, function ($sql, $bindings) {
            $statement = $this->getPdo()->prepare($sql);
            $this->bindValues($statement, $bindings);
            $statement->execute();
            return $statement->rowCount();
        });
    }

    /**
     * 执行原始语句（无返回值）
     * 
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数
     * @return bool 是否成功
     */
    public function statement(string $sql, array $bindings = []): bool
    {
        return $this->run($sql, $bindings, function ($sql, $bindings) {
            $statement = $this->getPdo()->prepare($sql);
            $this->bindValues($statement, $bindings);
            return $statement->execute();
        });
    }

    /**
     * 执行原始 SQL（不使用预处理）
     * 
     * @param string $sql SQL 语句
     * @return int 影响的行数
     */
    public function unprepared(string $sql): int
    {
        return $this->run($sql, [], function ($sql) {
            return $this->getPdo()->exec($sql);
        });
    }

    /**
     * 运行查询
     * 
     * @param string $sql SQL 语句
     * @param array $bindings 绑定参数
     * @param callable $callback 执行回调
     * @return mixed
     */
    protected function run(string $sql, array $bindings, callable $callback): mixed
    {
        $start = microtime(true);

        try {
            $result = $callback($sql, $bindings);
        } catch (PDOException $e) {
            // 尝试重连
            if ($this->causedByLostConnection($e) && $this->reconnectAttempts < $this->maxReconnectAttempts) {
                $this->reconnect();
                return $this->run($sql, $bindings, $callback);
            }
            
            throw $e;
        }

        $time = round((microtime(true) - $start) * 1000, 2);

        if ($this->loggingQueries) {
            $this->queryLog[] = compact('sql', 'bindings', 'time');
        }

        return $result;
    }

    /**
     * 绑定参数值
     * 
     * @param PDOStatement $statement PDO 语句
     * @param array $bindings 绑定参数
     * @return void
     */
    protected function bindValues(PDOStatement $statement, array $bindings): void
    {
        foreach (array_values($bindings) as $key => $value) {
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };

            $statement->bindValue($key + 1, $value, $type);
        }
    }

    /**
     * 检查是否因连接丢失导致的异常
     * 
     * @param PDOException $e 异常
     * @return bool
     */
    protected function causedByLostConnection(PDOException $e): bool
    {
        $message = $e->getMessage();
        
        $lostConnectionMessages = [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
        ];

        foreach ($lostConnectionMessages as $msg) {
            if (stripos($message, $msg) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 重新连接
     * 
     * @return void
     */
    public function reconnect(): void
    {
        $this->disconnect();
        $this->reconnectAttempts++;
        $this->connect();
    }

    /**
     * 断开连接
     * 
     * @return void
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }

    /**
     * 开始事务
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->getPdo()->beginTransaction();
    }

    /**
     * 提交事务
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->getPdo()->commit();
    }

    /**
     * 回滚事务
     * 
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->getPdo()->rollBack();
    }

    /**
     * 执行事务
     * 
     * @param callable $callback 事务回调
     * @return mixed 回调返回值
     * @throws \Throwable
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * 开启查询日志
     * 
     * @return void
     */
    public function enableQueryLog(): void
    {
        $this->loggingQueries = true;
    }

    /**
     * 关闭查询日志
     * 
     * @return void
     */
    public function disableQueryLog(): void
    {
        $this->loggingQueries = false;
    }

    /**
     * 获取查询日志
     * 
     * @return array
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * 清空查询日志
     * 
     * @return void
     */
    public function flushQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * 获取驱动名称
     * 
     * @return string
     */
    abstract public function getDriverName(): string;

    /**
     * 检查表是否存在
     * 
     * @param string $table 表名
     * @return bool
     */
    abstract public function tableExists(string $table): bool;

    /**
     * 获取表的列信息
     * 
     * @param string $table 表名
     * @return array
     */
    abstract public function getColumns(string $table): array;
}
