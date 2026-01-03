<?php

/**
 * HolySword Framework - 数据验证器
 * 
 * 提供强大的数据验证功能，支持多种验证规则。
 * 支持链式规则、自定义错误消息和点表示法访问嵌套字段。
 * 
 * @package    HolySword
 * @subpackage Validation
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Validation;

/**
 * 数据验证器
 * 
 * 支持的验证规则：
 * - required: 必填
 * - email: 邮箱格式
 * - numeric: 数字
 * - integer: 整数
 * - string: 字符串
 * - array: 数组
 * - min:n: 最小值/长度
 * - max:n: 最大值/长度
 * - between:min,max: 范围
 * - in:a,b,c: 枚举值
 * - url: URL 格式
 * - ip: IP 地址
 * - date: 日期格式
 * - alpha: 纯字母
 * - alpha_num: 字母数字
 * - confirmed: 确认字段
 * - boolean: 布尔值
 * - json: JSON 格式
 * 
 * 使用示例:
 * ```php
 * $validator = Validator::make($request->all(), [
 *     'name' => 'required|min:2|max:50',
 *     'email' => 'required|email',
 *     'age' => 'numeric|min:1|max:120',
 * ]);
 * 
 * if ($validator->fails()) {
 *     return Response::error($validator->firstError(), 422, 422);
 * }
 * ```
 * 
 * @package HolySword\Validation
 */
class Validator
{
    /**
     * 要验证的数据
     * 
     * @var array
     */
    protected array $data;

    /**
     * 验证规则
     * 
     * @var array
     */
    protected array $rules;

    /**
     * 验证错误信息
     * 
     * @var array
     */
    protected array $errors = [];

    /**
     * 自定义错误消息
     * 
     * @var array
     */
    protected array $messages = [];

    /**
     * 默认错误消息模板
     * 
     * @var array
     */
    protected array $defaultMessages = [
        'required' => ':field 不能为空',
        'email' => ':field 必须是有效的邮箱地址',
        'numeric' => ':field 必须是数字',
        'integer' => ':field 必须是整数',
        'string' => ':field 必须是字符串',
        'array' => ':field 必须是数组',
        'min' => ':field 最小值/长度为 :param',
        'max' => ':field 最大值/长度为 :param',
        'between' => ':field 必须在 :param 之间',
        'in' => ':field 必须是以下值之一: :param',
        'url' => ':field 必须是有效的 URL',
        'ip' => ':field 必须是有效的 IP 地址',
        'date' => ':field 必须是有效的日期',
        'alpha' => ':field 只能包含字母',
        'alpha_num' => ':field 只能包含字母和数字',
        'confirmed' => ':field 两次输入不一致',
        'boolean' => ':field 必须是布尔值',
        'json' => ':field 必须是有效的 JSON',
    ];

    /**
     * 创建验证器实例
     * 
     * @param array $data 要验证的数据
     * @param array $rules 验证规则
     * @param array $messages 自定义错误消息
     */
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = array_merge($this->defaultMessages, $messages);
        $this->validate();
    }

    /**
     * 静态工厂方法创建验证器
     * 
     * @param array $data 要验证的数据
     * @param array $rules 验证规则
     * @param array $messages 自定义错误消息
     * @return self 验证器实例
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    /**
     * 执行验证
     * 
     * @return void
     */
    protected function validate(): void
    {
        foreach ($this->rules as $field => $rules) {
            $rulesArray = is_string($rules) ? explode('|', $rules) : $rules;
            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $rule);
            }
        }
    }

    /**
     * 应用单个验证规则
     * 
     * @param string $field 字段名
     * @param string $rule 规则名称
     * @return void
     */
    protected function applyRule(string $field, string $rule): void
    {
        $params = [];
        if (str_contains($rule, ':')) {
            [$rule, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }

        $value = $this->getValue($field);
        $method = 'validate' . str_replace('_', '', ucwords($rule, '_'));

        if (method_exists($this, $method)) {
            if (!$this->$method($field, $value, $params)) {
                $this->addError($field, $rule, $params);
            }
        }
    }

    /**
     * 获取字段值（支持点表示法）
     * 
     * @param string $field 字段名
     * @return mixed 字段值
     */
    protected function getValue(string $field): mixed
    {
        $keys = explode('.', $field);
        $value = $this->data;
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }
        return $value;
    }

    /**
     * 添加验证错误
     * 
     * @param string $field 字段名
     * @param string $rule 规则名
     * @param array $params 规则参数
     * @return void
     */
    protected function addError(string $field, string $rule, array $params = []): void
    {
        $message = $this->messages[$rule] ?? "验证失败";
        $message = str_replace(':field', $field, $message);
        $message = str_replace(':param', implode(',', $params), $message);
        $this->errors[$field][] = $message;
    }

    // ==================== 验证规则 ====================

    /**
     * 验证必填字段
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateRequired(string $field, mixed $value, array $params): bool
    {
        if (is_null($value)) return false;
        if (is_string($value) && trim($value) === '') return false;
        if (is_array($value) && count($value) === 0) return false;
        return true;
    }

    /**
     * 验证邮箱格式
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateEmail(string $field, mixed $value, array $params): bool
    {
        if (empty($value)) return true;
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * 验证数字
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateNumeric(string $field, mixed $value, array $params): bool
    {
        if (empty($value) && $value !== 0 && $value !== '0') return true;
        return is_numeric($value);
    }

    /**
     * 验证整数
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateInteger(string $field, mixed $value, array $params): bool
    {
        if (empty($value) && $value !== 0 && $value !== '0') return true;
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * 验证字符串
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateString(string $field, mixed $value, array $params): bool
    {
        if (is_null($value)) return true;
        return is_string($value);
    }

    /**
     * 验证数组
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateArray(string $field, mixed $value, array $params): bool
    {
        if (is_null($value)) return true;
        return is_array($value);
    }

    /**
     * 验证最小值/长度
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateMin(string $field, mixed $value, array $params): bool
    {
        if (empty($value) && $value !== 0 && $value !== '0') return true;
        $min = (float) ($params[0] ?? 0);

        if (is_numeric($value)) return (float) $value >= $min;
        if (is_string($value)) return mb_strlen($value) >= $min;
        if (is_array($value)) return count($value) >= $min;
        return false;
    }

    /**
     * 验证最大值/长度
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateMax(string $field, mixed $value, array $params): bool
    {
        if (empty($value) && $value !== 0 && $value !== '0') return true;
        $max = (float) ($params[0] ?? 0);

        if (is_numeric($value)) return (float) $value <= $max;
        if (is_string($value)) return mb_strlen($value) <= $max;
        if (is_array($value)) return count($value) <= $max;
        return false;
    }

    /**
     * 验证范围
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数 [min, max]
     * @return bool 是否通过
     */
    protected function validateBetween(string $field, mixed $value, array $params): bool
    {
        if (empty($value)) return true;
        $min = (float) ($params[0] ?? 0);
        $max = (float) ($params[1] ?? 0);

        if (is_numeric($value)) {
            $val = (float) $value;
            return $val >= $min && $val <= $max;
        }
        if (is_string($value)) {
            $len = mb_strlen($value);
            return $len >= $min && $len <= $max;
        }
        return false;
    }

    /**
     * 验证枚举值
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 允许的值列表
     * @return bool 是否通过
     */
    protected function validateIn(string $field, mixed $value, array $params): bool
    {
        if (empty($value)) return true;
        return in_array($value, $params, true);
    }

    /**
     * 验证 URL 格式
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateUrl(string $field, mixed $value, array $params): bool
    {
        if (empty($value)) return true;
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * 验证 IP 地址
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateIp(string $field, mixed $value, array $params): bool
    {
        if (empty($value)) return true;
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * 验证日期格式
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateDate(string $field, mixed $value, array $params): bool
    {
        if (empty($value)) return true;
        return strtotime($value) !== false;
    }

    /**
     * 验证纯字母
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateAlpha(string $field, mixed $value, array $params): bool
    {
        if (empty($value)) return true;
        return preg_match('/^[\pL]+$/u', $value) === 1;
    }

    /**
     * 验证字母数字
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateAlphaNum(string $field, mixed $value, array $params): bool
    {
        if (empty($value)) return true;
        return preg_match('/^[\pL\pN]+$/u', $value) === 1;
    }

    /**
     * 验证确认字段
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateConfirmed(string $field, mixed $value, array $params): bool
    {
        return $value === $this->getValue($field . '_confirmation');
    }

    /**
     * 验证布尔值
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateBoolean(string $field, mixed $value, array $params): bool
    {
        if (is_null($value)) return true;
        return in_array($value, [true, false, 0, 1, '0', '1'], true);
    }

    /**
     * 验证 JSON 格式
     * 
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @param array $params 参数
     * @return bool 是否通过
     */
    protected function validateJson(string $field, mixed $value, array $params): bool
    {
        if (empty($value)) return true;
        if (!is_string($value)) return false;
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    // ==================== 结果方法 ====================

    /**
     * 检查验证是否失败
     * 
     * @return bool 是否有错误
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * 检查验证是否通过
     * 
     * @return bool 是否通过
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * 获取所有错误信息
     * 
     * @return array 错误信息数组
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * 获取第一个错误信息
     * 
     * @return string|null 第一个错误或 null
     */
    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        return null;
    }

    /**
     * 获取验证通过的数据
     * 
     * @return array 验证通过的字段数据
     */
    public function validated(): array
    {
        $validated = [];
        foreach (array_keys($this->rules) as $field) {
            $value = $this->getValue($field);
            if ($value !== null) {
                $validated[$field] = $value;
            }
        }
        return $validated;
    }
}
