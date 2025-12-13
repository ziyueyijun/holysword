<?php

/**
 * HolySword Framework - 认证中间件
 * 
 * 验证请求中的身份认证信息，支持 Bearer Token 和 API Key。
 * 
 * @package    App
 * @subpackage Middleware
 * @author     HolySword Team
 * @copyright  Copyright (c) 2025 HolySword
 * @license    MIT License
 * @version    1.0.0
 */

declare(strict_types=1);

namespace App\Middleware;

use Closure;
use HolySword\Http\Request;
use HolySword\Http\Response;
use HolySword\Middleware\MiddlewareInterface;

/**
 * 认证中间件
 * 
 * 验证请求中的 Authorization 头或 Token 参数。
 * 支持 Bearer Token 和 API Key 两种认证方式。
 * 
 * 使用示例:
 * ```php
 * // 在路由中使用
 * $router->aliasMiddleware('auth', AuthMiddleware::class);
 * $router->get('/api/user', [UserController::class, 'profile'])->middleware('auth');
 * 
 * // 请求时携带 Token
 * // Header: Authorization: Bearer your-token-here
 * // 或 URL: /api/user?token=your-token-here
 * ```
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * 有效的 API Token 列表
     * 实际项目中应从数据库或缓存中获取
     */
    protected array $validTokens = [
        'demo-token-12345',
        'test-api-key-67890',
    ];

    /**
     * 处理请求
     * 
     * 验证请求中的认证信息，验证通过后继续执行
     * 
     * @param Request $request HTTP 请求对象
     * @param Closure $next 下一个中间件
     * @return Response HTTP 响应
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->getTokenFromRequest($request);

        if (empty($token)) {
            return Response::error('未提供认证凭证', 401, 401);
        }

        if (!$this->validateToken($token)) {
            return Response::error('认证凭证无效', 401, 401);
        }

        // 认证通过，继续处理请求
        return $next($request);
    }

    /**
     * 从请求中获取 Token
     * 
     * 按优先级从以下位置获取:
     * 1. Authorization Header (Bearer Token)
     * 2. X-API-Key Header
     * 3. URL 参数 token
     * 4. POST 数据 token
     * 
     * @param Request $request HTTP 请求对象
     * @return string|null Token 字符串或 null
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        // 1. 从 Authorization 头获取 Bearer Token
        $authHeader = $request->header('authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // 2. 从 X-API-Key 头获取
        $apiKey = $request->header('x-api-key');
        if ($apiKey) {
            return $apiKey;
        }

        // 3. 从 URL 参数获取
        $token = $request->query('token');
        if ($token) {
            return $token;
        }

        // 4. 从 POST 数据获取
        $token = $request->input('token');
        if ($token) {
            return $token;
        }

        return null;
    }

    /**
     * 验证 Token 有效性
     * 
     * 实际项目中应查询数据库或验证 JWT
     * 
     * @param string $token 待验证的 Token
     * @return bool Token 是否有效
     */
    protected function validateToken(string $token): bool
    {
        // 简单验证：检查是否在有效 Token 列表中
        // 实际项目应验证 JWT 签名、过期时间等
        return in_array($token, $this->validTokens, true);
    }

    /**
     * 设置有效的 Token 列表
     * 
     * @param array $tokens Token 列表
     * @return self
     */
    public function setValidTokens(array $tokens): self
    {
        $this->validTokens = $tokens;
        return $this;
    }

    /**
     * 添加有效 Token
     * 
     * @param string $token 要添加的 Token
     * @return self
     */
    public function addToken(string $token): self
    {
        $this->validTokens[] = $token;
        return $this;
    }
}
