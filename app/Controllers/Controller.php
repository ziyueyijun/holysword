<?php

/**
 * HolySword Framework - 基础控制器
 * 
 * 所有应用控制器的基类，提供通用功能和辅助方法。
 * 
 * @package    App
 * @subpackage Controllers
 * @author     HolySword Team
 * @version    1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use HolySword\Http\Controller as BaseController;
use HolySword\Http\Response;

/**
 * 控制器基类
 * 
 * 应用中的所有控制器都应该继承此类。
 * 可以在这里添加通用的控制器方法和属性。
 * 
 * 使用示例：
 * ```php
 * class UserController extends Controller
 * {
 *     public function index()
 *     {
 *         return $this->success(['message' => '获取成功']);
 *     }
 *     
 *     public function error()
 *     {
 *         return $this->error('操作失败');
 *     }
 * }
 * ```
 */
class Controller extends BaseController
{
    // 继承父类的 success()、error()、json() 等方法
    // 可根据需要在此添加通用的控制器方法
}
