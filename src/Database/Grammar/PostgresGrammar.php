<?php

/**
 * HolySword Framework - PostgreSQL 语法类
 * 
 * 实现 PostgreSQL 特定的 SQL 语法生成。
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
 * PostgreSQL 语法类
 * 
 * PostgreSQL 使用双引号 " 包装标识符
 * 
 * @package HolySword\Database\Grammar
 */
class PostgresGrammar extends Grammar
{
    /**
     * PostgreSQL 特有的操作符
     * 
     * @var array
     */
    protected array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'ilike', 'not ilike',
        'between', 'not between',
        'in', 'not in',
        'is null', 'is not null',
        '~', '~*', '!~', '!~*',  // 正则表达式
        'similar to', 'not similar to',
        '@>', '<@', '?', '?|', '?&',  // JSON 操作符
        '@@', '@@@',  // 全文搜索
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
     * PostgreSQL 使用 TO_CHAR
     * 
     * @param string $column 列名
     * @param string $format 格式
     * @return string
     */
    public function dateFormat(string $column, string $format): string
    {
        $column = $this->wrapColumn($column);
        
        // 转换 PHP 日期格式为 PostgreSQL 格式
        $pgFormat = $this->convertDateFormat($format);
        
        return "TO_CHAR({$column}, '{$pgFormat}')";
    }

    /**
     * 获取日期函数
     * 
     * @param string $column 列名
     * @return string
     */
    public function date(string $column): string
    {
        return "{$this->wrapColumn($column)}::DATE";
    }

    /**
     * 转换 PHP 日期格式为 PostgreSQL 格式
     * 
     * @param string $format PHP 日期格式
     * @return string PostgreSQL 日期格式
     */
    protected function convertDateFormat(string $format): string
    {
        $replacements = [
            'Y' => 'YYYY',
            'y' => 'YY',
            'm' => 'MM',
            'n' => 'FMMM',
            'd' => 'DD',
            'j' => 'FMDD',
            'H' => 'HH24',
            'h' => 'HH12',
            'i' => 'MI',
            's' => 'SS',
            'A' => 'AM',
        ];

        return strtr($format, $replacements);
    }

    /**
     * 编译 INSERT 语句（支持 RETURNING）
     * 
     * @param string $table 表名
     * @param array $columns 列名
     * @param string|null $returning 返回的列名
     * @return string
     */
    public function compileInsertReturning(string $table, array $columns, ?string $returning = 'id'): string
    {
        $sql = $this->compileInsert($table, $columns);
        
        if ($returning) {
            $sql .= ' RETURNING ' . $this->wrapColumn($returning);
        }
        
        return $sql;
    }

    /**
     * 获取自增ID查询
     * 
     * PostgreSQL 使用 RETURNING
     * 
     * @return string|null
     */
    public function getLastInsertIdQuery(): ?string
    {
        return null; // 使用 RETURNING 子句
    }

    /**
     * 编译 JSON 提取表达式
     * 
     * PostgreSQL 使用 -> 和 ->> 操作符
     * 
     * @param string $column 列名
     * @param string $path JSON 路径
     * @param bool $asText 是否作为文本返回
     * @return string
     */
    public function compileJsonExtract(string $column, string $path, bool $asText = true): string
    {
        $operator = $asText ? '->>' : '->';
        return "{$this->wrapColumn($column)}{$operator}'{$path}'";
    }

    /**
     * 编译 ILIKE（大小写不敏感 LIKE）
     * 
     * @param string $column 列名
     * @return string
     */
    public function compileILike(string $column): string
    {
        return $this->wrapColumn($column) . ' ILIKE ?';
    }

    /**
     * 编译全文搜索
     * 
     * PostgreSQL 使用 tsvector 和 tsquery
     * 
     * @param string $column 列名
     * @param string $config 配置（如 'english'）
     * @return string
     */
    public function compileFullTextSearch(string $column, string $config = 'english'): string
    {
        return "to_tsvector('{$config}', {$this->wrapColumn($column)}) @@ plainto_tsquery('{$config}', ?)";
    }

    /**
     * 编译 UPSERT（INSERT ... ON CONFLICT）
     * 
     * @param string $table 表名
     * @param array $insertColumns 插入列
     * @param array $conflictColumns 冲突列
     * @param array $updateColumns 更新列
     * @return string
     */
    public function compileUpsert(string $table, array $insertColumns, array $conflictColumns, array $updateColumns): string
    {
        $insert = $this->compileInsert($table, $insertColumns);
        
        $conflict = array_map(fn($col) => $this->wrapValue($col), $conflictColumns);
        
        $updates = [];
        foreach ($updateColumns as $column) {
            $wrapped = $this->wrapValue($column);
            $updates[] = "{$wrapped} = EXCLUDED.{$wrapped}";
        }
        
        return $insert . ' ON CONFLICT (' . implode(', ', $conflict) . ') DO UPDATE SET ' . implode(', ', $updates);
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
     * 获取锁定语句
     * 
     * @param bool $forUpdate 是否为更新锁
     * @return string
     */
    public function compileLock(bool $forUpdate = true): string
    {
        return $forUpdate ? 'FOR UPDATE' : 'FOR SHARE';
    }

    /**
     * 编译数组类型操作
     * 
     * @param string $column 列名
     * @param string $operator 操作符（@> 包含, <@ 被包含）
     * @return string
     */
    public function compileArrayOperation(string $column, string $operator = '@>'): string
    {
        return $this->wrapColumn($column) . " {$operator} ?";
    }
}
