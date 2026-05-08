<?php
namespace app\controller\sso\Test; 
 
use think\facade\Request;
use think\facade\Db;

class Info
{
    /**
     * 测试
     */
    public function Info()
    { 
        return success('SSO单点登录-测试成功', '登录成功');
    }
}