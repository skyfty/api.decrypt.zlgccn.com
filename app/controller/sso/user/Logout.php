<?php
namespace app\controller\sso\user;

use app\common\JwtAuth;
use think\facade\Request;
use think\facade\Db; 

class Logout
{
    /**
     * 用户登出（单点登出）
     */
    public function logout()
    {
        $user = request()->user;
        if (!$user) {
            return error('未获取到用户信息', 401);
        }
        
        // 1. 清除用户令牌
        Db::name('sso_users')
            ->where('id', $user['id'])
            ->update([
                'access_token' => null,
                'refresh_token' => null,
                'token_expire_time' => null,
                'refresh_token_expire' => null,  // 补充清除刷新令牌过期时间
                'update_time' => date('Y-m-d H:i:s', time())
            ]);
        
        // 2. 同步取消该用户所有客户端授权（单点登出核心）
        Db::name('sso_user_clients')
            ->where('user_id', $user['id'])
            ->update([
                'status' => 0,  // 0-已取消授权
                'update_time' => date('Y-m-d H:i:s', time())
            ]);
            
        return success([
            'userId' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ], '登出成功');
    } 
}
