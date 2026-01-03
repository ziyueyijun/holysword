<?php

/**
 * HolySword Framework - SQLite 语法类
 * 
 * 实现 SQLite 特定的 SQL 语法生成。
 * 
 * @package    HolySword
 * @subpackage Database\Grammar
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Database\Grammar;

/**
 * SQLite 语法类
 * 
 * SQLite 使用双引号 " 包装标识符
 * 
 * @package HolySword\Database\Grammar
 */
class SqliteGrammar extends Grammar
{
    /**
     * SQLite 特有的操作符
     * 
     * @var array
     */
    protected array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'glob',
        'between', 'not between',
        'in', 'not in',
        'is null', 'is not null',
        'match',
    ];

    /**
     * 包装值（使用双引号）
     * 
     * @param string $value 值
     * @return string
     */
    public function wrapValue(string $value): string
    {
        if ($value === '*') {
            return $value;
        }

        // 处理已经被引用的值
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            return $value;
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }

    /**
     * 获取日期格式化函数
     * 
     * SQLite 使用 strftime
     * 
     * @param string $column 列名
     * @param string $format 格式
     * @return string
     */
    public function dateFormat(string $column, string $format): string
    {
        $column = $this->wrapColumn($column);
        
        // 转换 PHP 日期格式为 SQLite 格式
        $sqliteFormat = $this->convertDateFormat($format);
        
        return "strftime('{$sqliteFormat}', {$column})";
    }

    /**
     * 获取日期函数
     * 
     * @param string $column 列名
     * @return string
     */
    public function date(string $column): string
    {
        return "DATE({$this->wrapColumn($column)})";
    }

    /**
     * 转换 PHP 日期格式为 SQLite 格式
     * 
     * @param string $format PHP 日期格式
     * @return string SQLite 日期格式
     */
    protected function convertDateFormat(string $format): string
    {
        $replacements = [
            'Y' => '%Y',
            'y' => '%y',
            'm' => '%m',
            'd' => '%d',
            'H' => '%H',
            'i' => '%M',
            's' => '%S',
            'j' => '%j',  // 一年中的第几天
            'W' => '%W',  // 周数
        ];

        return strtr($format, $replacements);
    }

    /**
     * 编译 TRUNCATE 语句
     * 
     * SQLite 不支持 TRUNCATE，使用 DELETE
     * 
     * @param string $table 表名
     * @return string
     */
    public function compileTruncate(string $table): string
    {
        return "DELETE FROM " . $this->wrapTable($table);
    }

    /**
     * 编译 JSON 提取表达式
     * 
     * SQLite 3.38+ 支持 -> 和 ->> 操作符
     * 
     * @param string $column 列名
     * @param string $path JSON 路径
     * @return string
     */
    public function compileJsonExtract(string $column, string $path): string
    {
        return "json_extract({$this->wrapColumn($column)}, '$.{$path}')";
    }

    /**
     * 编译 UPSERT（INSERT OR REPLACE）
     * 
     * @param string $table 表名
     * @param array $columns 列名
     * @return string
     */
    public function compileUpsert(string $table, array $columns): string
    {
        $table = $this->wrapTable($table);
        $wrappedColumns = array_map(fn($col) => $this->wrapValue($col), $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        return "INSERT OR REPLACE INTO {$table} (" . implode(', ', $wrappedColumns) . ") VALUES ({$placeholders})";
    }

    /**
     * 编译随机排序
     * 
     * @return string
     */
    public function compileRandomOrder(): string
    {
        return 'RANDOM()';
    }

    /**
     * 获取当前时间表达式
     * 
     * SQLite 使用 datetime('now')
     * 
     * @return string
     */
    public function currentTimestamp(): string
    {
        return "datetime('now')";
    }

    /**
     * 编译 CASE 敏感的 LIKE
     * 
     * SQLite 默认 LIKE 不区分大小写
     * 
     * @param string $column 列名
     * @return string
     */
    public function compileGlob(string $column): string
    {
        return $this->wrapColumn($column) . ' GLOB ?';
    }
}
