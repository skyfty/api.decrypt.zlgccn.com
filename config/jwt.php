<?php

return [
    'access_key' => env('jwt.access_key', 'access-key-1234567890'), // 支持环境变量
    'refresh_key' => env('jwt.refresh_key', 'refresh-key-0987654321'),
    // 'access_expire' => 3600, // 访问令牌有效期
    // 'refresh_expire' => 604800, // 刷新令牌有效期
    'access_expire' => 3600, // 访问令牌有效期
    'refresh_expire' => 604800, // 刷新令牌有效期
];