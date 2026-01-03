<?php

/**
 * HolySword Framework - MySQL 语法类
 * 
 * 实现 MySQL 特定的 SQL 语法生成。
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
 * MySQL 语法类
 * 
 * MySQL 使用反引号 ` 包装标识符
 * 
 * @package HolySword\Database\Grammar
 */
class MySqlGrammar extends Grammar
{
    /**
     * MySQL 特有的操作符
     * 
     * @var array
     */
    protected array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'like binary',
        'between', 'not between',
        'in', 'not in',
        'is null', 'is not null',
        'regexp', 'not regexp', 'rlike',
        '&', '|', '^', '<<', '>>',
        'sounds like',
    ];

    /**
     * 包装值（使用反引号）
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
        if (str_starts_with($value, '`') && str_ends_with($value, '`')) {
            return $value;
        }

        return '`' . str_replace('`', '``', $value) . '`';
    }

    /**
     * 编译 LIMIT 和 OFFSET（MySQL 标准语法）
     * 
     * @param int|null $limit 限制数量
     * @param int|null $offset 偏移量
     * @return string
     */
    public function compileLimit(?int $limit, ?int $offset = null): string
    {
        $sql = '';

        if ($limit !== null) {
            $sql .= "LIMIT {$limit}";
            
            if ($offset !== null && $offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }

        return $sql;
    }

    /**
     * 获取日期格式化函数
     * 
     * MySQL 使用 DATE_FORMAT
     * 
     * @param string $column 列名
     * @param string $format 格式（PHP格式，需转换为MySQL格式）
     * @return string
     */
    public function dateFormat(string $column, string $format): string
    {
        $column = $this->wrapColumn($column);
        
        // 转换 PHP 日期格式为 MySQL 格式
        $mysqlFormat = $this->convertDateFormat($format);
        
        return "DATE_FORMAT({$column}, '{$mysqlFormat}')";
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
     * 转换 PHP 日期格式为 MySQL 格式
     * 
     * @param string $format PHP 日期格式
     * @return string MySQL 日期格式
     */
    protected function convertDateFormat(string $format): string
    {
        $replacements = [
            'Y' => '%Y',  // 四位年份
            'y' => '%y',  // 两位年份
            'm' => '%m',  // 月份（01-12）
            'n' => '%c',  // 月份（1-12）
            'd' => '%d',  // 日（01-31）
            'j' => '%e',  // 日（1-31）
            'H' => '%H',  // 小时（00-23）
            'G' => '%k',  // 小时（0-23）
            'h' => '%h',  // 小时（01-12）
            'g' => '%l',  // 小时（1-12）
            'i' => '%i',  // 分钟
            's' => '%s',  // 秒
            'A' => '%p',  // AM/PM
        ];

        return strtr($format, $replacements);
    }

    /**
     * 编译 JSON 提取表达式
     * 
     * MySQL 5.7+ 支持 JSON 列
     * 
     * @param string $column 列名
     * @param string $path JSON 路径
     * @return string
     */
    public function compileJsonExtract(string $column, string $path): string
    {
        return "JSON_EXTRACT({$this->wrapColumn($column)}, '$.{$path}')";
    }

    /**
     * 编译 JSON 包含查询
     * 
     * @param string $column 列名
     * @param mixed $value 值
     * @return string
     */
    public function compileJsonContains(string $column, mixed $value): string
    {
        return "JSON_CONTAINS({$this->wrapColumn($column)}, ?)";
    }

    /**
     * 编译全文搜索
     * 
     * MySQL FULLTEXT 搜索
     * 
     * @param array $columns 列名数组
     * @param string $value 搜索值
     * @param string $mode 模式（NATURAL LANGUAGE | BOOLEAN）
     * @return string
     */
    public function compileFullTextSearch(array $columns, string $value, string $mode = 'NATURAL LANGUAGE'): string
    {
        $wrappedColumns = array_map(fn($col) => $this->wrapColumn($col), $columns);
        $columnStr = implode(', ', $wrappedColumns);
        
        return "MATCH ({$columnStr}) AGAINST (? IN {$mode} MODE)";
    }

    /**
     * 编译 UPSERT（INSERT ... ON DUPLICATE KEY UPDATE）
     * 
     * @param string $table 表名
     * @param array $insertColumns 插入列
     * @param array $updateColumns 更新列
     * @return string
     */
    public function compileUpsert(string $table, array $insertColumns, array $updateColumns): string
    {
        $insert = $this->compileInsert($table, $insertColumns);
        
        $updates = [];
        foreach ($updateColumns as $column) {
            $wrapped = $this->wrapValue($column);
            $updates[] = "{$wrapped} = VALUES({$wrapped})";
        }
        
        return $insert . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    }

    /**
     * 编译随机排序
     * 
     * @return string
     */
    public function compileRandomOrder(): string
    {
        return 'RAND()';
    }

    /**
     * 获取锁定语句（行锁）
     * 
     * @param bool $forUpdate 是否为更新锁
     * @return string
     */
    public function compileLock(bool $forUpdate = true): string
    {
        return $forUpdate ? 'FOR UPDATE' : 'LOCK IN SHARE MODE';
    }
}
