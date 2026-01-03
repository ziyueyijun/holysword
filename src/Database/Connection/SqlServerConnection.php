<?php

/**
 * HolySword Framework - SQL Server 连接类
 * 
 * 实现 SQL Server (MSSQL) 数据库的连接和配置。
 * 支持 sqlsrv 和 dblib 两种 PDO 驱动。
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
use HolySword\Database\Grammar\Grammar;
use HolySword\Database\Grammar\SqlServerGrammar;

/**
 * SQL Server 连接类
 * 
 * @package HolySword\Database\Connection
 */
class SqlServerConnection extends Connection
{
    /**
     * 创建 SQL Server 语法实例
     * 
     * @return Grammar
     */
    protected function createGrammar(): Grammar
    {
        return new SqlServerGrammar();
    }

    /**
     * 获取 SQL Server DSN
     * 
     * 支持 sqlsrv（Windows）和 dblib（Linux）驱动
     * 
     * @return string
     */
    protected function getDsn(): string
    {
        $driver = $this->getAvailableDriver();
        
        return match ($driver) {
            'sqlsrv' => $this->getSqlsrvDsn(),
            'dblib' => $this->getDblibDsn(),
            default => throw new \RuntimeException('没有可用的 SQL Server PDO 驱动'),
        };
    }

    /**
     * 获取可用的 SQL Server 驱动
     * 
     * @return string|null
     */
    protected function getAvailableDriver(): ?string
    {
        // 优先使用配置的驱动
        if (!empty($this->config['driver'])) {
            return $this->config['driver'];
        }

        $availableDrivers = PDO::getAvailableDrivers();

        if (in_array('sqlsrv', $availableDrivers)) {
            return 'sqlsrv';
        }

        if (in_array('dblib', $availableDrivers)) {
            return 'dblib';
        }

        return null;
    }

    /**
     * 获取 sqlsrv 驱动的 DSN（Windows）
     * 
     * @return string
     */
    protected function getSqlsrvDsn(): string
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 1433;
        $database = $this->config['database'] ?? '';

        $dsn = "sqlsrv:Server={$host},{$port}";

        if (!empty($database)) {
            $dsn .= ";Database={$database}";
        }

        // 应用连接池
        if ($this->config['pooling'] ?? true) {
            $dsn .= ';ConnectionPooling=1';
        }

        // 加密连接
        if ($this->config['encrypt'] ?? false) {
            $dsn .= ';Encrypt=1';
        }

        // 信任服务器证书
        if ($this->config['trust_server_certificate'] ?? false) {
            $dsn .= ';TrustServerCertificate=1';
        }

        // 应用名称
        if (!empty($this->config['app_name'])) {
            $dsn .= ";APP={$this->config['app_name']}";
        }

        return $dsn;
    }

    /**
     * 获取 dblib 驱动的 DSN（Linux/FreeTDS）
     * 
     * @return string
     */
    protected function getDblibDsn(): string
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 1433;
        $database = $this->config['database'] ?? '';

        $dsn = "dblib:host={$host}:{$port}";

        if (!empty($database)) {
            $dsn .= ";dbname={$database}";
        }

        // 字符集
        $charset = $this->config['charset'] ?? 'UTF-8';
        $dsn .= ";charset={$charset}";

        return $dsn;
    }

    /**
     * 获取默认 PDO 选项
     * 
     * @return array
     */
    protected function getDefaultOptions(): array
    {
        $options = parent::getDefaultOptions();

        // sqlsrv 特定选项
        if ($this->getAvailableDriver() === 'sqlsrv') {
            $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
        }

        return $options;
    }

    /**
     * 配置连接
     * 
     * @return void
     */
    protected function configureConnection(): void
    {
        // 设置日期格式
        $this->getPdo()->exec("SET DATEFORMAT ymd");

        // 设置 ANSI 模式
        $this->getPdo()->exec("SET ANSI_NULLS ON");
        $this->getPdo()->exec("SET ANSI_PADDING ON");
        $this->getPdo()->exec("SET ANSI_WARNINGS ON");

        // 设置隔离级别
        if (!empty($this->config['isolation_level'])) {
            $this->getPdo()->exec("SET TRANSACTION ISOLATION LEVEL {$this->config['isolation_level']}");
        }
    }

    /**
     * 获取驱动名称
     * 
     * @return string
     */
    public function getDriverName(): string
    {
        return 'sqlsrv';
    }

    /**
     * 检查表是否存在
     * 
     * @param string $table 表名
     * @return bool
     */
    public function tableExists(string $table): bool
    {
        $table = $this->tablePrefix . $table;
        $schema = $this->config['schema'] ?? 'dbo';
        
        $sql = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
        
        $result = $this->selectOne($sql, [$schema, $table]);
        
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * 获取表的列信息
     * 
     * @param string $table 表名
     * @return array
     */
    public function getColumns(string $table): array
    {
        $table = $this->tablePrefix . $table;
        $schema = $this->config['schema'] ?? 'dbo';
        
        $sql = "SELECT 
                    c.COLUMN_NAME as name,
                    c.DATA_TYPE as type,
                    c.CHARACTER_MAXIMUM_LENGTH as max_length,
                    c.IS_NULLABLE as nullable,
                    c.COLUMN_DEFAULT as default_value,
                    COLUMNPROPERTY(OBJECT_ID(c.TABLE_SCHEMA + '.' + c.TABLE_NAME), c.COLUMN_NAME, 'IsIdentity') as is_identity
                FROM INFORMATION_SCHEMA.COLUMNS c
                WHERE c.TABLE_SCHEMA = ? AND c.TABLE_NAME = ?
                ORDER BY c.ORDINAL_POSITION";
        
        return $this->select($sql, [$schema, $table]);
    }

    /**
     * 获取表的主键
     * 
     * @param string $table 表名
     * @return array
     */
    public function getPrimaryKey(string $table): array
    {
        $table = $this->tablePrefix . $table;
        $schema = $this->config['schema'] ?? 'dbo';
        
        $sql = "SELECT col.COLUMN_NAME as column_name
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
                JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE col 
                    ON tc.CONSTRAINT_NAME = col.CONSTRAINT_NAME
                WHERE tc.TABLE_SCHEMA = ? 
                    AND tc.TABLE_NAME = ?
                    AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'";
        
        return $this->select($sql, [$schema, $table]);
    }

    /**
     * 获取 SQL Server 版本
     * 
     * @return string
     */
    public function getVersion(): string
    {
        $result = $this->selectOne('SELECT @@VERSION as version');
        return $result['version'] ?? 'unknown';
    }

    /**
     * 获取当前数据库名
     * 
     * @return string
     */
    public function getCurrentDatabase(): string
    {
        $result = $this->selectOne('SELECT DB_NAME() as name');
        return $result['name'] ?? '';
    }

    /**
     * 检查是否支持快照隔离
     * 
     * @return bool
     */
    public function supportsSnapshotIsolation(): bool
    {
        $database = $this->config['database'] ?? '';
        
        $result = $this->selectOne(
            "SELECT snapshot_isolation_state FROM sys.databases WHERE name = ?",
            [$database]
        );
        
        return ($result['snapshot_isolation_state'] ?? 0) == 1;
    }
}
