<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

// // 允许所有来源（生产环境建议指定域名）
header('Access-Control-Allow-Origin: *');

// 允许的请求方法
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

// 允许的请求头
header('Access-Control-Allow-Headers: Content-Type, access-token, refresh-token, X-Requested-With');

// 是否允许发送 Cookie
header('Access-Control-Allow-Credentials: false');

// 如果是预检请求（OPTIONS），直接返回 204
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require __DIR__ . '/../vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new App())->http;

$response = $http->run();

$response->send();

$http->end($response);
