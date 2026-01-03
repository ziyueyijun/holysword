<?php

/**
 * HolySword Framework - MySQL 连接类
 * 
 * 实现 MySQL 数据库的连接和配置。
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
use HolySword\Database\Grammar\MySqlGrammar;

/**
 * MySQL 连接类
 * 
 * @package HolySword\Database\Connection
 */
class MySqlConnection extends Connection
{
    /**
     * 创建 MySQL 语法实例
     * 
     * @return Grammar
     */
    protected function createGrammar(): Grammar
    {
        return new MySqlGrammar();
    }

    /**
     * 获取 MySQL DSN
     * 
     * @return string
     */
    protected function getDsn(): string
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 3306;
        $database = $this->config['database'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port}";

        if (!empty($database)) {
            $dsn .= ";dbname={$database}";
        }

        $dsn .= ";charset={$charset}";

        // Unix socket 连接
        if (!empty($this->config['unix_socket'])) {
            $dsn = "mysql:unix_socket={$this->config['unix_socket']}";
            if (!empty($database)) {
                $dsn .= ";dbname={$database}";
            }
            $dsn .= ";charset={$charset}";
        }

        return $dsn;
    }

    /**
     * 获取默认 PDO 选项
     * 
     * @return array
     */
    protected function getDefaultOptions(): array
    {
        $charset = $this->config['charset'] ?? 'utf8mb4';
        $collation = $this->config['collation'] ?? 'utf8mb4_unicode_ci';
        
        return array_merge(parent::getDefaultOptions(), [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$charset}' COLLATE '{$collation}'",
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ]);
    }

    /**
     * 配置连接
     * 
     * @return void
     */
    protected function configureConnection(): void
    {
        $statements = [];

        // 设置时区
        if (!empty($this->config['timezone'])) {
            $statements[] = "SET time_zone = '{$this->config['timezone']}'";
        }

        // 设置 SQL 模式
        if (isset($this->config['strict']) && $this->config['strict']) {
            $statements[] = "SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
        }

        foreach ($statements as $statement) {
            $this->getPdo()->exec($statement);
        }
    }

    /**
     * 获取驱动名称
     * 
     * @return string
     */
    public function getDriverName(): string
    {
        return 'mysql';
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
        $database = $this->config['database'] ?? '';
        
        $sql = "SELECT COUNT(*) as count FROM information_schema.tables 
                WHERE table_schema = ? AND table_name = ?";
        
        $result = $this->selectOne($sql, [$database, $table]);
        
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
        $database = $this->config['database'] ?? '';
        
        $sql = "SELECT 
                    COLUMN_NAME as name,
                    DATA_TYPE as type,
                    COLUMN_TYPE as full_type,
                    IS_NULLABLE as nullable,
                    COLUMN_DEFAULT as default_value,
                    COLUMN_KEY as key_type,
                    EXTRA as extra,
                    COLUMN_COMMENT as comment
                FROM information_schema.columns 
                WHERE table_schema = ? AND table_name = ?
                ORDER BY ORDINAL_POSITION";
        
        return $this->select($sql, [$database, $table]);
    }

    /**
     * 获取表的索引信息
     * 
     * @param string $table 表名
     * @return array
     */
    public function getIndexes(string $table): array
    {
        $table = $this->tablePrefix . $table;
        
        $sql = "SHOW INDEX FROM `{$table}`";
        
        return $this->select($sql);
    }

    /**
     * 获取表的外键信息
     * 
     * @param string $table 表名
     * @return array
     */
    public function getForeignKeys(string $table): array
    {
        $table = $this->tablePrefix . $table;
        $database = $this->config['database'] ?? '';
        
        $sql = "SELECT 
                    CONSTRAINT_NAME as name,
                    COLUMN_NAME as column_name,
                    REFERENCED_TABLE_NAME as foreign_table,
                    REFERENCED_COLUMN_NAME as foreign_column
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE table_schema = ? 
                    AND table_name = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL";
        
        return $this->select($sql, [$database, $table]);
    }
}
