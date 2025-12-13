<?php

/**
 * HolySword Framework - 功能演示控制器
 * 
 * 该控制器包含框架所有功能的演示方法，包括请求处理、
 * 响应输出、路由参数、Cookie 等。
 * 
 * @package    App
 * @subpackage Controllers
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
 * 功能演示控制器
 * 
 * 提供框架各项功能的演示示例，包括请求、响应、路由参数等。
 * 
 * 使用示例:
 * ```php
 * // GET 请求演示
 * GET /api/demo/request-info
 * GET /api/demo/query?name=John&age=25
 * 
 * // POST 请求演示
 * POST /api/demo/post
 * POST /api/demo/json (Content-Type: application/json)
 * 
 * // 路由参数
 * GET /api/demo/users/123
 * GET /api/demo/posts/123/comments/456
 * ```
 * 
 * @package App\Controllers
 */
class DemoController extends Controller
{
    /**
     * 演示：获取请求基本信息
     * 
     * 返回请求的方法、URI、路径、IP、User-Agent 等信息
     * 
     * @param Request $request HTTP 请求对象
     * @return Response JSON 响应
     */
    public function requestInfo(Request $request): Response
    {
        return $this->success([
            'method' => $request->method(),
            'uri' => $request->uri(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_ajax' => $request->isAjax(),
            'expects_json' => $request->expectsJson(),
        ]);
    }

    /**
     * 演示：获取 Query 参数（GET 参数）
     * 
     * 测试: /api/demo/query?name=John&age=25
     * 
     * @param Request $request HTTP 请求对象
     * @return Response JSON 响应
     */
    public function queryParams(Request $request): Response
    {
        return $this->success([
            'all_query' => $request->query(),
            'name' => $request->query('name', 'default'),
            'age' => $request->query('age', 0),
        ]);
    }

    /**
     * 演示：获取 POST 数据
     * 
     * 测试: POST /api/demo/post (form data: name=John&email=john@example.com)
     * 
     * @param Request $request HTTP 请求对象
     * @return Response JSON 响应
     */
    public function postData(Request $request): Response
    {
        return $this->success([
            'all_post' => $request->post(),
            'name' => $request->post('name'),
            'email' => $request->post('email'),
        ]);
    }

    /**
     * 演示：获取 JSON 请求体
     * 
     * 测试: POST /api/demo/json (Content-Type: application/json, Body: {"name":"John","age":25})
     * 
     * @param Request $request HTTP 请求对象
     * @return Response JSON 响应
     */
    public function jsonData(Request $request): Response
    {
        return $this->success([
            'all_json' => $request->json(),
            'name' => $request->json('name'),
            'age' => $request->json('age'),
            'raw_content' => $request->getContent(),
        ]);
    }

    /**
     * 演示：获取请求头
     * 
     * @param Request $request HTTP 请求对象
     * @return Response JSON 响应
     */
    public function headers(Request $request): Response
    {
        return $this->success([
            'all_headers' => $request->header(),
            'content_type' => $request->header('content-type'),
            'accept' => $request->header('accept'),
            'authorization' => $request->header('authorization', 'not provided'),
        ]);
    }

    /**
     * 演示：获取 Cookie
     * 
     * @param Request $request HTTP 请求对象
     * @return Response JSON 响应
     */
    public function cookies(Request $request): Response
    {
        return $this->success([
            'all_cookies' => $request->cookie(),
            'session_id' => $request->cookie('session_id', 'not set'),
        ]);
    }

    /**
     * 演示：input() 混合获取数据（GET + POST）
     * 
     * 测试: POST /api/demo/input?query_param=1 (form: post_param=2)
     * 
     * @param Request $request HTTP 请求对象
     * @return Response JSON 响应
     */
    public function inputData(Request $request): Response
    {
        return $this->success([
            'all_input' => $request->all(),
            'has_name' => $request->has('name'),
            'specific_input' => $request->input('name', 'default'),
        ]);
    }

    /**
     * 演示：only() 和 except() 过滤数据
     * 
     * @param Request $request HTTP 请求对象
     * @return Response JSON 响应
     */
    public function filterData(Request $request): Response
    {
        return $this->success([
            'only_name_email' => $request->only(['name', 'email']),
            'except_password' => $request->except(['password', 'token']),
        ]);
    }

    /**
     * 演示：路由参数
     * 
     * 测试: GET /api/demo/users/123
     * 
     * @param Request $request HTTP 请求对象
     * @param string $id 用户 ID
     * @return Response JSON 响应
     */
    public function routeParams(Request $request, string $id): Response
    {
        return $this->success([
            'user_id' => $id,
            'message' => "获取用户 {$id} 的信息",
        ]);
    }

    /**
     * 演示：多个路由参数
     * 
     * 测试: GET /api/demo/posts/123/comments/456
     * 
     * @param Request $request HTTP 请求对象
     * @param string $postId 文章 ID
     * @param string $commentId 评论 ID
     * @return Response JSON 响应
     */
    public function multiParams(Request $request, string $postId, string $commentId): Response
    {
        return $this->success([
            'post_id' => $postId,
            'comment_id' => $commentId,
            'message' => "获取文章 {$postId} 的评论 {$commentId}",
        ]);
    }

    /**
     * 演示：创建资源（POST）
     * 
     * @param Request $request HTTP 请求对象
     * @return Response JSON 响应
     */
    public function store(Request $request): Response
    {
        $data = $request->json() ?: $request->post();
        
        return $this->success([
            'id' => rand(1000, 9999),
            'created_data' => $data,
            'message' => '资源创建成功',
        ], '创建成功');
    }

    /**
     * 演示：更新资源（PUT/PATCH）
     * 
     * @param Request $request HTTP 请求对象
     * @param string $id 资源 ID
     * @return Response JSON 响应
     */
    public function update(Request $request, string $id): Response
    {
        $data = $request->json() ?: $request->post();
        
        return $this->success([
            'id' => $id,
            'updated_data' => $data,
            'message' => "资源 {$id} 更新成功",
        ], '更新成功');
    }

    /**
     * 演示：删除资源（DELETE）
     * 
     * @param Request $request HTTP 请求对象
     * @param string $id 资源 ID
     * @return Response JSON 响应
     */
    public function destroy(Request $request, string $id): Response
    {
        return $this->success([
            'id' => $id,
            'message' => "资源 {$id} 已删除",
        ], '删除成功');
    }

    /**
     * 演示：错误响应
     * 
     * @param Request $request HTTP 请求对象
     * @return Response JSON 响应
     */
    public function errorDemo(Request $request): Response
    {
        $type = $request->query('type', 'default');
        
        switch ($type) {
            case '400':
                return Response::error('请求参数错误', 400, 400);
            case '401':
                return Response::error('未授权访问', 401, 401);
            case '403':
                return Response::error('禁止访问', 403, 403);
            case '404':
                return Response::error('资源未找到', 404, 404);
            case '500':
                return Response::error('服务器内部错误', 500, 500);
            default:
                return $this->error('默认错误示例');
        }
    }

    /**
     * 演示：重定向
     * 
     * @param Request $request HTTP 请求对象
     * @return Response 重定向响应
     */
    public function redirectDemo(Request $request): Response
    {
        $url = $request->query('url', '/api/status');
        return Response::redirect($url);
    }

    /**
     * 演示：设置 Cookie
     * 
     * @param Request $request HTTP 请求对象
     * @return Response JSON 响应（带 Cookie）
     */
    public function setCookie(Request $request): Response
    {
        $name = $request->query('name', 'demo_cookie');
        $value = $request->query('value', 'hello_world');
        
        return $this->success([
            'message' => "Cookie '{$name}' 已设置",
            'value' => $value,
        ])->cookie($name, $value, 60); // 60分钟过期
    }

    /**
     * 演示：自定义响应头
     * 
     * @param Request $request HTTP 请求对象
     * @return Response JSON 响应（带自定义头）
     */
    public function customHeaders(Request $request): Response
    {
        return $this->success([
            'message' => '查看响应头可以看到自定义头',
        ])
        ->header('X-Custom-Header', 'Hello')
        ->header('X-Framework', 'HolySword')
        ->header('X-Version', '1.0.0');
    }

    /**
     * 演示：文件上传信息
     * 
     * 测试: POST /api/demo/upload (multipart/form-data with file)
     * 
     * @param Request $request HTTP 请求对象
     * @return Response JSON 响应
     */
    public function uploadInfo(Request $request): Response
    {
        $files = $request->file();
        
        if (empty($files)) {
            return $this->error('没有检测到上传的文件');
        }
        
        $fileInfo = [];
        foreach ($files as $name => $file) {
            $fileInfo[$name] = [
                'name' => $file['name'] ?? 'unknown',
                'type' => $file['type'] ?? 'unknown',
                'size' => $file['size'] ?? 0,
                'error' => $file['error'] ?? -1,
            ];
        }
        
        return $this->success([
            'files' => $fileInfo,
            'message' => '文件信息获取成功（注意：这只是演示，文件并未保存）',
        ]);
    }
}
