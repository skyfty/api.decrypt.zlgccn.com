<?php
namespace app\controller\sso\user;

use app\common\JwtAuth;
use think\facade\Request;
use think\facade\Db;  
class Login
{
    /**
     * 用户登录，返回令牌对
     */
    public function login()
    {
        $data = Request::post();
        
        // 1. 参数验证
        $errors = [];
        if (empty($data['username'])) {
            $errors[] = '用户名不能为空';
        }
        
        if (empty($data['password'])) {
            $errors[] = '密码不能为空';
        }
        
        // 若有错误，返回
        if (!empty($errors)) {
            return error(implode('；', $errors), 400);
        }
        
        // 2. 查询用户（并检查状态，如是否禁用）
        $user = Db::name('sso_users')
            ->where('username', $data['username'])
            ->find();
        
        // 3. 验证用户存在性、密码、状态
        if (!$user) {
            return error('用户名或密码错误', 401);
        }
        if (!password_verify($data['password'], $user['password'])) {
            return error('用户名或密码错误', 401);
        }
        if (isset($user['status']) && $user['status'] != 1) { // 假设有status字段：1-正常，0-禁用
            return error('账号已被禁用', 403);
        }
        
        // 4. 生成令牌
        $payload = [
            'userId' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        $tokens = JwtAuth::generateTokenPair($payload);
        
        // 5. 更新用户登录信息
        $loginIp = Request::ip();
        Db::name('sso_users')
            ->where('id', $user['id'])
            ->update([ 
                'last_login_ip' => $loginIp,
                'last_login_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'), 
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'token_expire_time' => date('Y-m-d H:i:s', time() + JwtAuth::getAccessExpire()),
                'refresh_token_expire' => date('Y-m-d H:i:s', time() + JwtAuth::getRefreshExpire()),
            ]);
        
        return success([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in'],
            'userInfo' => $payload
        ], '登录成功');
    } 
}
