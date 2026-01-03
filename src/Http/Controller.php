<?php

/**
 * HolySword Framework - 控制器基类
 * 
 * 该文件定义了框架控制器的基类，提供了标准的响应构建方法。
 * 所有应用控制器都应该继承此类以获得统一的响应处理能力。
 * 
 * @package    HolySword
 * @subpackage Http
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace HolySword\Http;

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
 * namespace App\Controllers;
 * 
 * use HolySword\Http\Controller;
 * use HolySword\Http\Request;
 * use HolySword\Http\Response;
 * 
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
 * @package HolySword\Http
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
     * // 返回用户数据
     * return $this->success(['user' => $user], '用户创建成功');
     * 
     * // 返回空数据
     * return $this->success(null, '操作成功');
     * 
     * // 返回列表数据
     * return $this->success($users, '获取成功');
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
     * // 参数错误
     * return $this->error('参数不能为空');
     * 
     * // 资源不存在
     * return $this->error('用户不存在', 1001, 404);
     * 
     * // 权限不足
     * return $this->error('没有操作权限', 1002, 403);
     * 
     * // 服务器错误
     * return $this->error('服务器繁忙，请稍后重试', 5000, 500);
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
     * 适用于需要自定义响应结构的场景
     * 
     * @param mixed $data 要序列化为 JSON 的数据
     * @param int $status HTTP 状态码，默认为 200
     * @return Response JSON 响应对象
     * 
     * @example
     * ```php
     * // 自定义响应格式
     * return $this->json(['custom' => 'data'], 201);
     * 
     * // 返回原始数据
     * return $this->json($apiResponse);
     * ```
     */
    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /**
     * 返回分页响应
     * 
     * 构建一个带分页信息的 JSON 响应
     * 
     * @param array $data 列表数据
     * @param array $pagination 分页信息
     * @return Response JSON 格式的分页响应
     * 
     * @example
     * ```php
     * $users = User::forPage($page, $perPage)->get();
     * $total = User::count();
     * 
     * return $this->paginate($users, [
     *     'total' => $total,
     *     'per_page' => $perPage,
     *     'current_page' => $page,
     *     'last_page' => ceil($total / $perPage),
     * ]);
     * ```
     */
    protected function paginate(array $data, array $pagination): Response
    {
        return Response::paginate($data, $pagination);
    }

    /**
     * 返回验证错误响应
     * 
     * 构建一个验证失败的 JSON 响应
     * 
     * @param string $message 错误消息
     * @param array $errors 详细错误信息
     * @return Response JSON 格式的验证错误响应
     * 
     * @example
     * ```php
     * $validator = validate($request->all(), ['name' => 'required']);
     * if ($validator->fails()) {
     *     return $this->validationError($validator->firstError(), $validator->errors());
     * }
     * ```
     */
    protected function validationError(string $message, array $errors = []): Response
    {
        return Response::validationError($message, $errors);
    }
}
