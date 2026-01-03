<?php

/**
 * HolySword Framework - PostgreSQL 连接类
 * 
 * 实现 PostgreSQL 数据库的连接和配置。
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

use HolySword\Database\Grammar\Grammar;
use HolySword\Database\Grammar\PostgresGrammar;

/**
 * PostgreSQL 连接类
 * 
 * @package HolySword\Database\Connection
 */
class PostgresConnection extends Connection
{
    /**
     * 创建 PostgreSQL 语法实例
     * 
     * @return Grammar
     */
    protected function createGrammar(): Grammar
    {
        return new PostgresGrammar();
    }

    /**
     * 获取 PostgreSQL DSN
     * 
     * @return string
     */
    protected function getDsn(): string
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 5432;
        $database = $this->config['database'] ?? '';

        $dsn = "pgsql:host={$host};port={$port}";

        if (!empty($database)) {
            $dsn .= ";dbname={$database}";
        }

        // SSL 模式
        if (!empty($this->config['sslmode'])) {
            $dsn .= ";sslmode={$this->config['sslmode']}";
        }

        return $dsn;
    }

    /**
     * 配置连接
     * 
     * @return void
     */
    protected function configureConnection(): void
    {
        $statements = [];

        // 设置字符集
        $charset = $this->config['charset'] ?? 'utf8';
        $statements[] = "SET NAMES '{$charset}'";

        // 设置时区
        if (!empty($this->config['timezone'])) {
            $statements[] = "SET timezone = '{$this->config['timezone']}'";
        }

        // 设置 schema 搜索路径
        if (!empty($this->config['schema'])) {
            $schema = is_array($this->config['schema']) 
                ? implode(', ', $this->config['schema']) 
                : $this->config['schema'];
            $statements[] = "SET search_path TO {$schema}";
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
        return 'pgsql';
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
        $schema = $this->config['schema'] ?? 'public';
        
        $sql = "SELECT EXISTS (
                    SELECT FROM information_schema.tables 
                    WHERE table_schema = ? AND table_name = ?
                ) as exists";
        
        $result = $this->selectOne($sql, [$schema, $table]);
        
        return $result['exists'] ?? false;
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
        $schema = $this->config['schema'] ?? 'public';
        
        $sql = "SELECT 
                    column_name as name,
                    data_type as type,
                    udt_name as udt_type,
                    is_nullable as nullable,
                    column_default as default_value,
                    character_maximum_length as max_length
                FROM information_schema.columns 
                WHERE table_schema = ? AND table_name = ?
                ORDER BY ordinal_position";
        
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
        $schema = $this->config['schema'] ?? 'public';
        
        $sql = "SELECT a.attname as column_name
                FROM pg_index i
                JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
                WHERE i.indrelid = ?::regclass AND i.indisprimary";
        
        return $this->select($sql, ["{$schema}.{$table}"]);
    }

    /**
     * 获取序列的下一个值
     * 
     * @param string $sequence 序列名
     * @return int
     */
    public function getNextSequenceValue(string $sequence): int
    {
        $result = $this->selectOne("SELECT nextval(?) as value", [$sequence]);
        return (int) ($result['value'] ?? 0);
    }
}
