<?php

/**
 * HolySword Framework - 控制器基类
 * 
 * 该文件定义了应用程序控制器的基类，提供了常用的响应方法。
 * 所有应用控制器都应该继承此类以获得统一的响应处理能力。
 * 
 * @package    HolySword
 * @subpackage App\Controllers
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use HolySword\Http\Request;
use HolySword\Http\Response;

/**
 * 控制器基类
 * 
 * 提供所有控制器共享的基础功能，包括：
 * - 成功响应构建
 * - 错误响应构建
 * - JSON 响应构建
 * 
 * 使用示例:
 * ```php
 * class UserController extends Controller
 * {
 *     public function index(Request $request): Response
 *     {
 *         $users = User::all();
 *         return $this->success($users, '获取成功');
 *     }
 * }
 * ```
 * 
 * @package App\Controllers
 */
abstract class Controller
{
    /**
     * 返回成功响应
     * 
     * 构建一个标准的成功 JSON 响应，包含 code=0 表示成功
     * 
     * @param mixed $data 响应数据，可以是数组、对象或 null
     * @param string $message 响应消息，默认为 'success'
     * @return Response JSON 格式的成功响应
     * 
     * @example
     * ```php
     * return $this->success(['user' => $user], '用户创建成功');
     * ```
     */
    protected function success(mixed $data = null, string $message = 'success'): Response
    {
        return Response::success($data, $message);
    }

    /**
     * 返回错误响应
     * 
     * 构建一个标准的错误 JSON 响应
     * 
     * @param string $message 错误消息
     * @param int $code 业务错误码，默认为 1
     * @param int $status HTTP 状态码，默认为 400
     * @return Response JSON 格式的错误响应
     * 
     * @example
     * ```php
     * return $this->error('用户不存在', 1001, 404);
     * ```
     */
    protected function error(string $message = 'error', int $code = 1, int $status = 400): Response
    {
        return Response::error($message, $code, $status);
    }

    /**
     * 返回 JSON 响应
     * 
     * 构建一个自定义的 JSON 响应，不使用标准格式
     * 
     * @param mixed $data 要序列化为 JSON 的数据
     * @param int $status HTTP 状态码，默认为 200
     * @return Response JSON 响应对象
     * 
     * @example
     * ```php
     * return $this->json(['custom' => 'data'], 201);
     * ```
     */
    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
}
