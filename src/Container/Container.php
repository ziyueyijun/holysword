<?php

/**
 * HolySword Framework - 依赖注入容器
 * 
 * @package    HolySword
 * @subpackage Container
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Container;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * 依赖注入容器类
 * 
 * 提供服务注册、解析和依赖自动注入功能。
 * 支持单例绑定、别名注册和自动依赖解析。
 * 
 * 使用示例:
 * ```php
 * // 获取容器实例
 * $container = Container::getInstance();
 * 
 * // 绑定服务
 * $container->bind('cache', FileCache::class);
 * 
 * // 绑定单例
 * $container->singleton('db', function($container) {
 *     return new Database($container->make('config'));
 * });
 * 
 * // 解析服务
 * $cache = $container->make('cache');
 * 
 * // 绑定已存在的实例
 * $container->instance('request', $request);
 * ```
 * 
 * @package HolySword\Container
 */
class Container
{
    /**
     * 容器单例实例
     * 
     * 用于存储全局唯一的容器实例，确保整个应用使用同一个容器
     * 
     * @var Container|null
     */
    protected static ?Container $instance = null;

    /**
     * 已绑定的服务映射表
     * 
     * 存储所有通过 bind() 或 singleton() 方法注册的服务定义
     * 格式: ['abstract' => ['concrete' => Closure|string, 'shared' => bool]]
     * 
     * @var array<string, array{concrete: Closure|string, shared: bool}>
     */
    protected array $bindings = [];

    /**
     * 已解析的单例实例缓存
     * 
     * 存储所有已解析的单例服务实例，避免重复创建
     * 格式: ['abstract' => instance]
     * 
     * @var array<string, mixed>
     */
    protected array $instances = [];

    /**
     * 服务别名映射表
     * 
     * 允许为服务注册别名，通过别名也可以解析服务
     * 格式: ['alias' => 'abstract']
     * 
     * @var array<string, string>
     */
    protected array $aliases = [];

    /**
     * 获取容器单例实例
     * 
     * 如果实例不存在则创建新实例，确保全局唯一
     * 
     * @return static 容器实例
     * 
     * @example
     * ```php
     * $container = Container::getInstance();
     * ```
     */
    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * 设置容器单例实例
     * 
     * 允许手动设置或重置容器单例，通常在应用初始化时使用
     * 
     * @param Container|null $container 要设置的容器实例，传 null 可清除单例
     * @return Container|null 设置后的容器实例
     */
    public static function setInstance(?Container $container = null): ?Container
    {
        return static::$instance = $container;
    }

    /**
     * 绑定服务到容器
     * 
     * 将抽象类型绑定到具体实现，支持类名或闭包
     * 
     * @param string $abstract 抽象类型（接口名、类名或自定义名称）
     * @param Closure|string|null $concrete 具体实现（类名或返回实例的闭包）
     * @param bool $shared 是否为单例绑定（默认 false）
     * @return void
     * 
     * @example
     * ```php
     * // 绑定类名
     * $container->bind(CacheInterface::class, FileCache::class);
     * 
     * // 绑定闭包
     * $container->bind('cache', function($container) {
     *     return new FileCache('/path/to/cache');
     * });
     * ```
     */
    public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
    {
        $this->dropStaleInstances($abstract);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * 绑定单例服务
     * 
     * 单例绑定会在首次解析后缓存实例，后续解析返回同一实例
     * 
     * @param string $abstract 抽象类型
     * @param Closure|string|null $concrete 具体实现
     * @return void
     * 
     * @example
     * ```php
     * $container->singleton('db', function($container) {
     *     return new Database($container->make('config')->get('database'));
     * });
     * ```
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * 绑定已存在的实例到容器
     * 
     * 将已创建的对象实例直接注册到容器中
     * 
     * @param string $abstract 抽象类型
     * @param mixed $instance 要绑定的实例
     * @return mixed 绑定的实例
     * 
     * @example
     * ```php
     * $request = new Request();
     * $container->instance('request', $request);
     * ```
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * 为服务注册别名
     * 
     * 允许通过别名访问已注册的服务
     * 
     * @param string $abstract 原始抽象类型
     * @param string $alias 别名
     * @return void
     * 
     * @example
     * ```php
     * $container->bind(CacheInterface::class, RedisCache::class);
     * $container->alias(CacheInterface::class, 'cache');
     * // 现在可以通过 'cache' 访问
     * $cache = $container->make('cache');
     * ```
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * 从容器解析服务
     * 
     * 解析并返回服务实例，自动处理依赖注入
     * 
     * @param string $abstract 要解析的抽象类型
     * @param array $parameters 构造函数参数覆盖
     * @return mixed 解析后的服务实例
     * @throws \RuntimeException 当类不存在或无法实例化时抛出
     * 
     * @example
     * ```php
     * // 基本解析
     * $cache = $container->make('cache');
     * 
     * // 传递参数
     * $logger = $container->make(Logger::class, ['level' => 'debug']);
     * ```
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * 内部解析服务逻辑
     * 
     * 处理别名解析、单例缓存和实例构建
     * 
     * @param string $abstract 抽象类型
     * @param array $parameters 构造函数参数
     * @return mixed 解析后的实例
     */
    protected function resolve(string $abstract, array $parameters = []): mixed
    {
        $abstract = $this->getAlias($abstract);

        // 如果已存在单例实例，直接返回
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        $object = $this->build($concrete, $parameters);

        // 如果是单例绑定，存储实例
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * 构建服务实例
     * 
     * 通过反射或闭包创建服务实例，自动解析构造函数依赖
     * 
     * @param Closure|string $concrete 具体实现（闭包或类名）
     * @param array $parameters 构造函数参数
     * @return mixed 构建的实例
     * @throws \RuntimeException 当类不存在或无法实例化时
     */
    protected function build(Closure|string $concrete, array $parameters = []): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new \RuntimeException("类 [{$concrete}] 不存在");
        }

        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException("类 [{$concrete}] 无法实例化");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete();
        }

        $dependencies = $this->resolveDependencies(
            $constructor->getParameters(),
            $parameters
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * 解析构造函数依赖列表
     * 
     * 遍历所有依赖参数，优先使用传入的参数，否则自动解析
     * 
     * @param array $dependencies ReflectionParameter 数组
     * @param array $parameters 用户提供的参数覆盖
     * @return array 解析后的依赖值数组
     */
    protected function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if (array_key_exists($dependency->getName(), $parameters)) {
                $results[] = $parameters[$dependency->getName()];
                continue;
            }

            $results[] = $this->resolveDependency($dependency);
        }

        return $results;
    }

    /**
     * 解析单个构造函数参数依赖
     * 
     * 根据参数类型自动从容器解析依赖，或使用默认值
     * 
     * @param ReflectionParameter $parameter 反射参数对象
     * @return mixed 解析后的依赖值
     * @throws \RuntimeException 当无法解析依赖且无默认值时
     */
    protected function resolveDependency(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type === null || $type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new \RuntimeException("无法解析依赖 [{$parameter->getName()}]");
        }

        return $this->make($type->getName());
    }

    /**
     * 获取抽象类型的具体实现
     * 
     * 查找已绑定的具体实现，如未绑定则返回原类型
     * 
     * @param string $abstract 抽象类型
     * @return Closure|string 具体实现（闭包或类名）
     */
    protected function getConcrete(string $abstract): Closure|string
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * 解析别名获取真实的抽象类型名
     * 
     * @param string $abstract 可能是别名的类型名
     * @return string 真实的抽象类型名
     */
    protected function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * 判断服务是否为单例绑定
     * 
     * @param string $abstract 抽象类型
     * @return bool 是否为单例
     */
    protected function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) ||
               (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared'] === true);
    }

    /**
     * 清除已缓存的实例
     * 
     * 在重新绑定时清除旧的单例实例
     * 
     * @param string $abstract 抽象类型
     * @return void
     */
    protected function dropStaleInstances(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    /**
     * 判断服务是否已绑定到容器
     * 
     * @param string $abstract 抽象类型
     * @return bool 是否已绑定
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]) || isset($this->aliases[$abstract]);
    }

    /**
     * 判断服务是否存在（PSR-11 兼容）
     * 
     * @param string $id 服务标识符
     * @return bool 服务是否存在
     */
    public function has(string $id): bool
    {
        return $this->bound($id);
    }

    /**
     * 获取服务实例（PSR-11 兼容）
     * 
     * @param string $id 服务标识符
     * @return mixed 服务实例
     */
    public function get(string $id): mixed
    {
        return $this->make($id);
    }
}
