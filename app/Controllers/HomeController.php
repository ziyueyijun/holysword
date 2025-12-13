<?php

/**
 * HolySword Framework - 首页控制器
 * 
 * 该文件定义了网站首页和介绍页面的控制器。
 * 作为框架的示例控制器，展示如何创建和使用控制器。
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
 * 首页控制器
 * 
 * 处理网站首页和关于页面的请求，返回框架基本信息。
 * 这是一个示例控制器，展示了如何使用 Request 和 Response 对象。
 * 
 * @package App\Controllers
 */
class HomeController extends Controller
{
    /**
     * 首页
     * 
     * 返回欢迎信息和请求基本信息
     * 
     * @param Request $request HTTP 请求对象
     * @return Response JSON 格式的欢迎信息
     */
    public function index(Request $request): Response
    {
        return $this->success([
            'message' => 'Welcome to HolySword Framework',
            'method' => $request->method(),
            'path' => $request->path(),
        ]);
    }

    /**
     * 关于页面
     * 
     * 返回框架的基本信息，包括名称、版本和描述
     * 
     * @return Response JSON 格式的框架信息
     */
    public function about(): Response
    {
        return $this->success([
            'name' => 'HolySword',
            'version' => '1.0.0',
            'description' => 'A lightweight PHP 8.1 framework built with native PHP',
        ]);
    }
}
