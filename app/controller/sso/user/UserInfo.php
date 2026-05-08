<?php
namespace app\controller\sso\user;

// use app\common\JwtAuth;
// use think\facade\Request;
// use think\facade\Db; 

class UserInfo
{
    /**
     * 获取当前用户信息（需要 Token 验证） 
     */
    public function userInfo()
    {
        $user = request()->user;
        
        if (!$user) {
            return error('未获取到用户信息', 401);
        }
        $filteredUser = filter_user_data($user);
        return success($filteredUser, '请求成功'); 
    }
}