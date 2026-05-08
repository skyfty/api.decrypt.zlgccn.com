<?php
namespace app\middleware;

use think\facade\Request;
use think\facade\Db;
use think\facade\Log; // 引入日志
use app\common\JwtAuth;

class AuthMiddleware
{
    public function handle($request, \Closure $next)
    {
        $access_token = Request::header('access-token');
        
        if (!$access_token) { 
            Log::warning('未提供访问令牌', ['url' => $request->url()]);
            return error('未提供访问令牌', 401);    
        }

        $userData = JwtAuth::verifyAccessToken($access_token);
        if (!$userData) {
            Log::warning('令牌验证失败', ['token' => $access_token, 'url' => $request->url()]);
            return error('登录已过期，请刷新令牌或重新登录', 401);  
        }

        $user = Db::name('sso_users')
            ->where('id', $userData['userId'])
            ->find();
        
        if (!$user) {
            Log::warning('用户不存在', ['userId' => $userData['userId']]);
            return error('用户不存在', 401);  
        }
        
        if ($user['access_token'] != $access_token) {
            Log::warning('令牌不匹配', ['userId' => $userData['userId'], 'token' => $access_token]);
            return error('账号已在其他设备登录，请重新登录', 401);   
        }
        
        // 令牌即将过期的逻辑保持不变
        $expireTime = strtotime($user['token_expire_time']);
        $currentTime = time();
        $request->user = $user;
        $request->userData = $userData;
        $request->tokenExpiringSoon = ($expireTime - $currentTime) < 300;

        return $next($request);
    }
}