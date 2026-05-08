<?php
namespace app\controller\sso\user;

use app\common\JwtAuth;
use think\facade\Request;
use think\facade\Db;  
class Auth
{
    /**
     * 刷新访问令牌
     */
    public function refreshToken()
    {
        $refreshToken = Request::post('refresh_token');
        
        if (empty($refreshToken)) {
            return error('刷新令牌不能为空', 400);
        }
        
        // 验证刷新令牌
        $userData = JwtAuth::verifyRefreshToken($refreshToken);
        if (!$userData) {
            return error('刷新令牌无效或已过期，请重新登录', 401);
        }
        
        // 检查数据库中的刷新令牌是否匹配
        $user = Db::name('sso_users')
            ->where('id', $userData['userId'])
            ->find();
            
        if (!$user || $user['refresh_token'] != $refreshToken) {
            return error('刷新令牌无效，请重新登录', 401);
        }
        
        // 生成新的令牌对
        $tokens = JwtAuth::generateTokenPair($userData);
        
        // 更新数据库中的令牌
        Db::name('sso_users')
            ->where('id', $userData['userId'])
            ->update([
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                // 'token_expire_time' => date('Y-m-d H:i:s', time() + 3600),
                // 'refresh_token_expire' => date('Y-m-d H:i:s', time() + 604800)
                'token_expire_time' => date('Y-m-d H:i:s', time() + JwtAuth::getAccessExpire()),
                'refresh_token_expire' => date('Y-m-d H:i:s', time() + JwtAuth::getRefreshExpire()),
            ]);
            
        return success([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in']
        ], '令牌刷新成功');
    }

    
    /**
     * 检查令牌有效性（供其他系统验证）
     */
    public function checkToken()
    { 
        $token = Request::header('access-token');
        if (empty($token)) {
            return error('令牌不能为空', 400);
        }
        
        // 验证访问令牌
        $userData = JwtAuth::verifyAccessToken($token);
        if (!$userData) {
            return error('令牌无效或已过期', 401);
        }
        
        // 检查数据库中的令牌是否有效
        $user = Db::name('sso_users')
            ->where('id', $userData['userId'])
            ->find();
            
        if (!$user || $user['access_token'] != $token) {
            return error('令牌已失效', 401);
        }
        
        return success([
            'valid' => true,
            'userInfo' => filter_user_data($user)
        ], '令牌有效');
    }
}
