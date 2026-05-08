<?php
// 应用公共文件  
// 数据过滤
if (!function_exists('filter_user_data')) {
    function filter_user_data($user)
    {
        // 定义需要返回的字段
        $allowedFields = ['id', 'username', 'role', 'avatar', 'create_time', 'update_time'];
        return array_intersect_key($user, array_flip($allowedFields));
    }
}

if (!function_exists('success')) {
    /**
     * 全局成功响应
     * @param array $data 返回的数据
     * @param string $msg 提示信息
     * @param int $code 状态码
     * @return \think\Response\Json
     */
    function success($data = [], $msg = '操作成功', $code = 200,  $success = true)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'success' => $success,
            'timestamp' => time()
        ]);
    }
}

if (!function_exists('error')) {
    /**
     * 全局失败响应
     * @param string $msg 提示信息
     * @param int $code 状态码
     * @param array $data 返回的数据
     * @return \think\Response\Json
     */
    function error($msg = '操作失败', $code = 500, $data = null, $success = false)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'success' => $success,
            'timestamp' => time()
        ]);
    }
}