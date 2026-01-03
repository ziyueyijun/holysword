<?php

/**
 * HolySword Framework - SQL Server 语法类
 * 
 * 实现 SQL Server (MSSQL) 特定的 SQL 语法生成。
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
 * SQL Server 语法类
 * 
 * SQL Server 使用方括号 [] 包装标识符
 * 
 * @package HolySword\Database\Grammar
 */
class SqlServerGrammar extends Grammar
{
    /**
     * SQL Server 特有的操作符
     * 
     * @var array
     */
    protected array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like',
        'between', 'not between',
        'in', 'not in',
        'is null', 'is not null',
        '&', '|', '^',  // 位运算
    ];

    /**
     * 包装值（使用方括号）
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
        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            return $value;
        }

        return '[' . str_replace(']', ']]', $value) . ']';
    }

    /**
     * 编译 LIMIT 和 OFFSET（SQL Server 2012+ 使用 OFFSET FETCH）
     * 
     * SQL Server 需要 ORDER BY 才能使用 OFFSET FETCH
     * 
     * @param int|null $limit 限制数量
     * @param int|null $offset 偏移量
     * @return string
     */
    public function compileLimit(?int $limit, ?int $offset = null): string
    {
        // SQL Server 2012+ 使用 OFFSET FETCH 语法
        // 注意：必须与 ORDER BY 一起使用
        if ($limit === null && $offset === null) {
            return '';
        }

        $offset = $offset ?? 0;
        
        $sql = "OFFSET {$offset} ROWS";
        
        if ($limit !== null) {
            $sql .= " FETCH NEXT {$limit} ROWS ONLY";
        }
        
        return $sql;
    }

    /**
     * 编译 TOP N 语法（旧版分页方式）
     * 
     * @param int $limit 限制数量
     * @return string
     */
    public function compileTop(int $limit): string
    {
        return "TOP ({$limit})";
    }

    /**
     * 获取日期格式化函数
     * 
     * SQL Server 使用 FORMAT 或 CONVERT
     * 
     * @param string $column 列名
     * @param string $format 格式
     * @return string
     */
    public function dateFormat(string $column, string $format): string
    {
        $column = $this->wrapColumn($column);
        
        // 转换 PHP 日期格式为 SQL Server 格式
        $sqlFormat = $this->convertDateFormat($format);
        
        // SQL Server 2012+ 使用 FORMAT
        return "FORMAT({$column}, '{$sqlFormat}')";
    }

    /**
     * 获取日期函数
     * 
     * @param string $column 列名
     * @return string
     */
    public function date(string $column): string
    {
        return "CAST({$this->wrapColumn($column)} AS DATE)";
    }

    /**
     * 转换 PHP 日期格式为 SQL Server 格式
     * 
     * SQL Server FORMAT 使用 .NET 格式
     * 
     * @param string $format PHP 日期格式
     * @return string SQL Server 日期格式
     */
    protected function convertDateFormat(string $format): string
    {
        $replacements = [
            'Y' => 'yyyy',
            'y' => 'yy',
            'm' => 'MM',
            'n' => 'M',
            'd' => 'dd',
            'j' => 'd',
            'H' => 'HH',
            'G' => 'H',
            'h' => 'hh',
            'g' => 'h',
            'i' => 'mm',
            's' => 'ss',
            'A' => 'tt',
        ];

        return strtr($format, $replacements);
    }

    /**
     * 获取自增ID查询
     * 
     * SQL Server 使用 SCOPE_IDENTITY()
     * 
     * @return string|null
     */
    public function getLastInsertIdQuery(): ?string
    {
        return 'SELECT SCOPE_IDENTITY() AS id';
    }

    /**
     * 获取当前时间表达式
     * 
     * @return string
     */
    public function currentTimestamp(): string
    {
        return 'GETDATE()';
    }

    /**
     * 编译 JSON 提取表达式
     * 
     * SQL Server 2016+ 支持 JSON
     * 
     * @param string $column 列名
     * @param string $path JSON 路径
     * @return string
     */
    public function compileJsonExtract(string $column, string $path): string
    {
        return "JSON_VALUE({$this->wrapColumn($column)}, '$.{$path}')";
    }

    /**
     * 编译 JSON 查询（返回 JSON 对象/数组）
     * 
     * @param string $column 列名
     * @param string $path JSON 路径
     * @return string
     */
    public function compileJsonQuery(string $column, string $path): string
    {
        return "JSON_QUERY({$this->wrapColumn($column)}, '$.{$path}')";
    }

    /**
     * 编译 UPSERT（MERGE 语句）
     * 
     * @param string $table 表名
     * @param array $insertColumns 插入列
     * @param array $matchColumns 匹配列
     * @param array $updateColumns 更新列
     * @return string
     */
    public function compileUpsert(string $table, array $insertColumns, array $matchColumns, array $updateColumns): string
    {
        $table = $this->wrapTable($table);
        
        // 构建 MERGE 语句
        $sql = "MERGE INTO {$table} AS target";
        $sql .= " USING (VALUES (";
        $sql .= implode(', ', array_fill(0, count($insertColumns), '?'));
        $sql .= ")) AS source (";
        $sql .= implode(', ', array_map(fn($col) => $this->wrapValue($col), $insertColumns));
        $sql .= ")";
        
        // ON 条件
        $conditions = [];
        foreach ($matchColumns as $col) {
            $wrapped = $this->wrapValue($col);
            $conditions[] = "target.{$wrapped} = source.{$wrapped}";
        }
        $sql .= " ON " . implode(' AND ', $conditions);
        
        // WHEN MATCHED
        if (!empty($updateColumns)) {
            $updates = [];
            foreach ($updateColumns as $col) {
                $wrapped = $this->wrapValue($col);
                $updates[] = "target.{$wrapped} = source.{$wrapped}";
            }
            $sql .= " WHEN MATCHED THEN UPDATE SET " . implode(', ', $updates);
        }
        
        // WHEN NOT MATCHED
        $sql .= " WHEN NOT MATCHED THEN INSERT (";
        $sql .= implode(', ', array_map(fn($col) => $this->wrapValue($col), $insertColumns));
        $sql .= ") VALUES (";
        $sql .= implode(', ', array_map(fn($col) => "source." . $this->wrapValue($col), $insertColumns));
        $sql .= ");";
        
        return $sql;
    }

    /**
     * 编译随机排序
     * 
     * @return string
     */
    public function compileRandomOrder(): string
    {
        return 'NEWID()';
    }

    /**
     * 获取锁定语句
     * 
     * SQL Server 使用表提示
     * 
     * @param bool $forUpdate 是否为更新锁
     * @return string
     */
    public function compileLock(bool $forUpdate = true): string
    {
        return $forUpdate ? 'WITH (UPDLOCK, ROWLOCK)' : 'WITH (HOLDLOCK, ROWLOCK)';
    }

    /**
     * 编译 TRUNCATE 语句
     * 
     * @param string $table 表名
     * @return string
     */
    public function compileTruncate(string $table): string
    {
        return "TRUNCATE TABLE " . $this->wrapTable($table);
    }

    /**
     * 编译字符串连接
     * 
     * SQL Server 使用 + 或 CONCAT
     * 
     * @param array $parts 要连接的部分
     * @return string
     */
    public function compileConcat(array $parts): string
    {
        $wrapped = array_map(fn($p) => is_string($p) && !str_contains($p, '(') ? "'{$p}'" : $p, $parts);
        return 'CONCAT(' . implode(', ', $wrapped) . ')';
    }

    /**
     * 编译 ISNULL 函数（类似 COALESCE）
     * 
     * @param string $column 列名
     * @param mixed $default 默认值
     * @return string
     */
    public function compileIsNull(string $column, mixed $default): string
    {
        $column = $this->wrapColumn($column);
        $defaultStr = is_string($default) ? "'{$default}'" : $default;
        
        return "ISNULL({$column}, {$defaultStr})";
    }
}
