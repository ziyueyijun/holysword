<?php

/**
 * HolySword Framework - 配置管理器
 * 
 * 该文件提供了统一的配置文件加载和访问功能。
 * 支持点表示法访问嵌套配置，例如 'app.name' 或 'database.mysql.host'。
 * 配置文件采用懒加载方式，仅在首次访问时加载。
 * 
 * @package    HolySword
 * @subpackage Config
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Config;

/**
 * 配置管理类
 * 
 * 提供配置文件的加载、读取、设置和合并功能。
 * 
 * 使用示例:
 * ```php
 * $config = new Config('/path/to/config');
 * 
 * // 获取配置值
 * $appName = $config->get('app.name');
 * 
 * // 设置配置值
 * $config->set('app.debug', true);
 * 
 * // 检查配置是否存在
 * if ($config->has('database.mysql')) {
 *     // ...
 * }
 * ```
 * 
 * @package HolySword\Config
 */
class Config
{
    /**
     * 配置项存储
     * 
     * 存储已加载的所有配置文件内容
     * 格式: ['filename' => [...配置内容...]]
     * 
     * @var array<string, array>
     */
    protected array $items = [];

    /**
     * 配置目录
     * 
     * 存储配置文件所在的目录路径
     * 
     * @var string
     */
    protected string $path;

    /**
     * 构造函数
     * 
     * @param string $path 配置文件目录的绝对路径
     */
    /**
     * 创建配置管理实例
     * 
     * @param string $path 配置文件目录的绝对路径
     */
    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/\\');
    }

    /**
     * 加载配置文件
     * 
     * 从配置目录加载指定名称的配置文件。
     * 配置文件应该返回一个数组。
     * 
     * @param string $name 配置文件名（不含 .php 后缀）
     * @return array 配置内容数组
     * 
     * @example
     * ```php
     * $appConfig = $config->load('app');
     * // 加载 config/app.php 文件
     * ```
     */
    public function load(string $name): array
    {
        if (isset($this->items[$name])) {
            return $this->items[$name];
        }

        $file = $this->path . DIRECTORY_SEPARATOR . $name . '.php';

        if (file_exists($file)) {
            $this->items[$name] = require $file;
        } else {
            $this->items[$name] = [];
        }

        return $this->items[$name];
    }

    /**
     * 获取配置项
     * 
     * 使用点表示法获取嵌套配置值
     * 
     * @param string $key 配置键，格式：file.key.subkey
     * @param mixed $default 默认值，当配置不存在时返回
     * @return mixed 配置值或默认值
     * 
     * @example
     * ```php
     * $name = $config->get('app.name', 'Default App');
     * $host = $config->get('database.mysql.host', '127.0.0.1');
     * ```
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $file = array_shift($keys);

        // 确保配置文件已加载
        $this->load($file);

        $value = $this->items[$file] ?? [];

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * 设置配置项
     * 
     * 使用点表示法设置嵌套配置值。
     * 如果中间路径不存在，会自动创建。
     * 
     * @param string $key 配置键，格式：file.key.subkey
     * @param mixed $value 要设置的值
     * @return void
     * 
     * @example
     * ```php
     * $config->set('app.debug', true);
     * $config->set('cache.driver', 'redis');
     * ```
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $file = array_shift($keys);

        // 确保配置文件已加载
        if (!isset($this->items[$file])) {
            $this->load($file);
        }

        if (empty($keys)) {
            $this->items[$file] = $value;
            return;
        }

        $array = &$this->items[$file];

        foreach ($keys as $segment) {
            if (!isset($array[$segment]) || !is_array($array[$segment])) {
                $array[$segment] = [];
            }
            $array = &$array[$segment];
        }

        $array = $value;
    }

    /**
     * 判断配置是否存在
     * 
     * @param string $key 配置键
     * @return bool 配置是否存在
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * 获取所有配置
     * 
     * 返回所有已加载的配置项
     * 
     * @return array 所有配置项
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * 合并配置
     * 
     * 将新的配置值合并到现有配置中
     * 
     * @param string $key 配置键
     * @param array $values 要合并的配置数组
     * @return void
     * 
     * @example
     * ```php
     * $config->merge('app', ['new_key' => 'new_value']);
     * ```
     */
    public function merge(string $key, array $values): void
    {
        $current = $this->get($key, []);

        if (is_array($current)) {
            $this->set($key, array_merge($current, $values));
        }
    }
}
