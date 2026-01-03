<?php

/**
 * HolySword Framework - SQLite 连接类
 * 
 * 实现 SQLite 数据库的连接和配置。
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
use HolySword\Database\Grammar\SqliteGrammar;

/**
 * SQLite 连接类
 * 
 * @package HolySword\Database\Connection
 */
class SqliteConnection extends Connection
{
    /**
     * 创建 SQLite 语法实例
     * 
     * @return Grammar
     */
    protected function createGrammar(): Grammar
    {
        return new SqliteGrammar();
    }

    /**
     * 获取 SQLite DSN
     * 
     * @return string
     */
    protected function getDsn(): string
    {
        $database = $this->config['database'] ?? ':memory:';
        
        // 支持内存数据库
        if ($database === ':memory:') {
            return 'sqlite::memory:';
        }
        
        return 'sqlite:' . $database;
    }

    /**
     * 配置连接
     * 
     * @return void
     */
    protected function configureConnection(): void
    {
        // 启用外键约束
        if ($this->config['foreign_key_constraints'] ?? true) {
            $this->getPdo()->exec('PRAGMA foreign_keys = ON');
        }

        // 设置忙等待超时（毫秒）
        if (!empty($this->config['busy_timeout'])) {
            $this->getPdo()->exec("PRAGMA busy_timeout = {$this->config['busy_timeout']}");
        }

        // 设置日志模式
        if (!empty($this->config['journal_mode'])) {
            $this->getPdo()->exec("PRAGMA journal_mode = {$this->config['journal_mode']}");
        }

        // 设置同步模式
        if (isset($this->config['synchronous'])) {
            $this->getPdo()->exec("PRAGMA synchronous = {$this->config['synchronous']}");
        }
    }

    /**
     * 获取驱动名称
     * 
     * @return string
     */
    public function getDriverName(): string
    {
        return 'sqlite';
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
        
        $sql = "SELECT COUNT(*) as count FROM sqlite_master 
                WHERE type = 'table' AND name = ?";
        
        $result = $this->selectOne($sql, [$table]);
        
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
        
        $sql = "PRAGMA table_info(\"{$table}\")";
        
        $columns = $this->select($sql);
        
        // 转换为统一格式
        return array_map(function ($col) {
            return [
                'name' => $col['name'],
                'type' => $col['type'],
                'nullable' => $col['notnull'] == 0,
                'default_value' => $col['dflt_value'],
                'primary_key' => $col['pk'] == 1,
            ];
        }, $columns);
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
        
        $sql = "PRAGMA index_list(\"{$table}\")";
        
        return $this->select($sql);
    }

    /**
     * 获取数据库版本
     * 
     * @return string
     */
    public function getVersion(): string
    {
        $result = $this->selectOne('SELECT sqlite_version() as version');
        return $result['version'] ?? 'unknown';
    }

    /**
     * 备份数据库到文件
     * 
     * @param string $destination 目标文件路径
     * @return bool
     */
    public function backup(string $destination): bool
    {
        $currentDatabase = $this->config['database'] ?? '';
        
        if (empty($currentDatabase) || $currentDatabase === ':memory:') {
            return false;
        }
        
        return copy($currentDatabase, $destination);
    }

    /**
     * 优化数据库（VACUUM）
     * 
     * @return bool
     */
    public function vacuum(): bool
    {
        return $this->statement('VACUUM');
    }
}
