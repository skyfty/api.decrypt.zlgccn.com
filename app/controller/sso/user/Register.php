<?php
namespace app\controller\sso\user;

use think\facade\Request;
use think\facade\Db;
use think\Validate;

class Register
{
    /**
     * 用户注册（仅创建用户，不自动登录）
     */
    public function register()
    {
        // 参数接收
        $data = Request::post();
        
        // 1. 参数验证
        $errors = [];
        
        // 验证用户名
        if (empty($data['username'])) {
            $errors[] = '用户名不能为空';
        } elseif (strlen($data['username']) < 3 || strlen($data['username']) > 10) {
            $errors[] = '用户名长度为3-10位';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors[] = '用户名仅支持字母、数字和下划线';
        }
        
        // 验证密码
        if (empty($data['password'])) {
            $errors[] = '密码不能为空';
        } elseif (strlen($data['password']) < 6 || strlen($data['password']) > 20) {
            $errors[] = '密码长度为6-20位';
        }
        
        // 验证客户端ID
        if (empty($data['client_id'])) {
            $errors[] = '客户端标识不能为空';
        } elseif (!is_numeric($data['client_id'])) {
            $errors[] = '客户端标识必须是数字';
        }
        
        // 验证角色（可选，默认user）
        $data['role'] = $data['role'] ?? 'user';
        if (!in_array($data['role'], ['user', 'admin'])) {
            $errors[] = '角色只能是user或admin';
        }
        
        // 若有错误，返回
        if (!empty($errors)) {
            return error(implode('；', $errors), 400);
        }
        
        // 2. 验证客户端合法性，并获取client_name
        $client = Db::name('sso_clients')
            ->where('id', $data['client_id'])
            ->where('status', 1)
            ->field('id, client_name')  // 查询需要的字段
            ->find();
        
        if (!$client) {
            return error('客户端标识无效', 400);
        }
        
        // 3. 验证用户名是否已存在
        $existUser = Db::name('sso_users')
            ->where('username', $data['username'])
            ->find();
        if ($existUser) {
            return error('用户名已存在', 400);
        }
        
        // 4. 密码加密
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // 5. 插入用户数据
        $userId = Db::name('sso_users')
            ->insertGetId([
                'username' => $data['username'],
                'password' => $hashedPassword,
                'role' => $data['role'],
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
                'access_token' => null,
                'refresh_token' => null,
                'token_expire_time' => null,
                'refresh_token_expire' => null,
            ]);
        
        if (!$userId) {
            return error('注册失败，请稍后重试', 500);
        }
        
        // 6. 记录用户与客户端关联
        Db::name('sso_user_clients')
            ->insert([
                'user_id' => $userId,
                'client_id' => $data['client_id'],
                'client_name' => $client['client_name'],
                'status' => 1,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);
        
        return success([
            'userId' => $userId,
            'username' => $data['username']
        ], '注册成功，请登录');
    }
}
