<?php

/**
 * HolySword Framework - 模型事件 Trait
 * 
 * 提供模型生命周期事件的触发和监听功能。
 * 
 * @package    HolySword
 * @subpackage Database\Model\Traits
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Database\Model\Traits;

/**
 * 模型事件 Trait
 * 
 * 支持的事件：
 * - creating / created：创建前/后
 * - updating / updated：更新前/后
 * - saving / saved：保存前/后（创建或更新）
 * - deleting / deleted：删除前/后
 * - restoring / restored：恢复前/后（软删除）
 * 
 * @package HolySword\Database\Model\Traits
 */
trait HasEvents
{
    /**
     * 事件监听器
     * 
     * @var array<string, array<callable>>
     */
    protected static array $eventListeners = [];

    /**
     * 事件观察者
     * 
     * @var array<string, object>
     */
    protected static array $observers = [];

    /**
     * 是否触发事件
     * 
     * @var bool
     */
    protected bool $fireEvents = true;

    /**
     * 注册事件监听器
     * 
     * @param string $event 事件名
     * @param callable $callback 回调函数
     * @return void
     */
    public static function registerEvent(string $event, callable $callback): void
    {
        $class = static::class;
        
        if (!isset(self::$eventListeners[$class])) {
            self::$eventListeners[$class] = [];
        }
        
        if (!isset(self::$eventListeners[$class][$event])) {
            self::$eventListeners[$class][$event] = [];
        }
        
        self::$eventListeners[$class][$event][] = $callback;
    }

    /**
     * 注册 creating 事件
     * 
     * @param callable $callback 回调函数
     * @return void
     */
    public static function creating(callable $callback): void
    {
        static::registerEvent('creating', $callback);
    }

    /**
     * 注册 created 事件
     * 
     * @param callable $callback 回调函数
     * @return void
     */
    public static function created(callable $callback): void
    {
        static::registerEvent('created', $callback);
    }

    /**
     * 注册 updating 事件
     * 
     * @param callable $callback 回调函数
     * @return void
     */
    public static function updating(callable $callback): void
    {
        static::registerEvent('updating', $callback);
    }

    /**
     * 注册 updated 事件
     * 
     * @param callable $callback 回调函数
     * @return void
     */
    public static function updated(callable $callback): void
    {
        static::registerEvent('updated', $callback);
    }

    /**
     * 注册 saving 事件
     * 
     * @param callable $callback 回调函数
     * @return void
     */
    public static function saving(callable $callback): void
    {
        static::registerEvent('saving', $callback);
    }

    /**
     * 注册 saved 事件
     * 
     * @param callable $callback 回调函数
     * @return void
     */
    public static function saved(callable $callback): void
    {
        static::registerEvent('saved', $callback);
    }

    /**
     * 注册 deleting 事件
     * 
     * @param callable $callback 回调函数
     * @return void
     */
    public static function deleting(callable $callback): void
    {
        static::registerEvent('deleting', $callback);
    }

    /**
     * 注册 deleted 事件
     * 
     * @param callable $callback 回调函数
     * @return void
     */
    public static function deleted(callable $callback): void
    {
        static::registerEvent('deleted', $callback);
    }

    /**
     * 注册 restoring 事件
     * 
     * @param callable $callback 回调函数
     * @return void
     */
    public static function restoring(callable $callback): void
    {
        static::registerEvent('restoring', $callback);
    }

    /**
     * 注册 restored 事件
     * 
     * @param callable $callback 回调函数
     * @return void
     */
    public static function restored(callable $callback): void
    {
        static::registerEvent('restored', $callback);
    }

    /**
     * 触发事件
     * 
     * @param string $event 事件名
     * @return bool 如果返回 false，则阻止操作
     */
    protected function fireModelEvent(string $event): bool
    {
        if (!$this->fireEvents) {
            return true;
        }

        $class = static::class;

        // 事件注册静态方法名单（这些方法用于注册回调，不是生命周期钩子）
        $eventRegistrationMethods = [
            'creating', 'created', 'updating', 'updated',
            'saving', 'saved', 'deleting', 'deleted',
            'restoring', 'restored', 'booting', 'booted'
        ];

        // 调用模型内部钩子方法（如 onSaving, onCreating 等）
        $hookMethod = 'on' . ucfirst($event);
        if (method_exists($this, $hookMethod)) {
            $result = $this->$hookMethod();
            if ($result === false) {
                return false;
            }
        }

        // 调用观察者
        if (isset(self::$observers[$class])) {
            foreach (self::$observers[$class] as $observer) {
                if (method_exists($observer, $event)) {
                    $result = $observer->$event($this);
                    if ($result === false) {
                        return false;
                    }
                }
            }
        }

        // 调用注册的监听器
        if (isset(self::$eventListeners[$class][$event])) {
            foreach (self::$eventListeners[$class][$event] as $callback) {
                $result = $callback($this);
                if ($result === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 注册观察者
     * 
     * @param object|string $observer 观察者实例或类名
     * @return void
     */
    public static function observe(object|string $observer): void
    {
        $class = static::class;
        
        if (is_string($observer)) {
            $observer = new $observer();
        }

        if (!isset(self::$observers[$class])) {
            self::$observers[$class] = [];
        }

        self::$observers[$class][] = $observer;
    }

    /**
     * 清除所有事件监听器
     * 
     * @return void
     */
    public static function flushEventListeners(): void
    {
        $class = static::class;
        
        unset(self::$eventListeners[$class]);
        unset(self::$observers[$class]);
    }

    /**
     * 禁用事件触发
     * 
     * @return static
     */
    public function withoutEvents(): static
    {
        $this->fireEvents = false;
        return $this;
    }

    /**
     * 启用事件触发
     * 
     * @return static
     */
    public function withEvents(): static
    {
        $this->fireEvents = true;
        return $this;
    }

    /**
     * 静态方法：在回调中禁用事件
     * 
     * @param callable $callback 回调函数
     * @return mixed
     */
    public static function withoutEventsOn(callable $callback): mixed
    {
        $model = new static();
        $model->fireEvents = false;

        try {
            return $callback($model);
        } finally {
            $model->fireEvents = true;
        }
    }

    /**
     * 模型启动时调用（静态初始化）
     * 
     * 子类可重写此方法注册事件
     * 
     * @return void
     */
    protected static function boot(): void
    {
        // 子类可重写
    }

    /**
     * 模型初始化
     * 
     * @return void
     */
    protected function initializeEvents(): void
    {
        static $booted = [];
        $class = static::class;

        if (!isset($booted[$class])) {
            $booted[$class] = true;
            static::boot();
        }
    }
}
