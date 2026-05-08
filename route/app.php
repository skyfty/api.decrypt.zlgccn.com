<?php

use think\facade\Route;

// 加载 v2 目录下的路由文件
$v2Files = glob(__DIR__ . '/v2/*.php');
foreach ($v2Files as $file) {
    include $file;
}

// --------------------------------------------------------------------------
// 基础测试路由
// --------------------------------------------------------------------------
Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});
Route::get('hello', 'index/hello');

// --------------------------------------------------------------------------
// SSO 单点登录相关路由（认证/用户信息）
// --------------------------------------------------------------------------
Route::group('sso/user', function () {
    // 登录 / 注册
    Route::post('login', 'sso.user.Login/login');
    Route::post('register', 'sso.user.Register/Register'); // 用户注册

    // 刷新 Token（带跨域）
    Route::post('refreshToken', 'sso.user.Auth/refreshToken')
        ->middleware(\app\middleware\Cors::class);

    // 需要登录鉴权的用户信息接口
    Route::group(function () {
        Route::get('userinfo', 'sso.user.UserInfo/userInfo');
        Route::get('logout', 'sso.user.Logout/logout');
        Route::post('checkToken', 'sso.user.Auth/checkToken');
    })->middleware([
        \app\middleware\Cors::class,
        \app\middleware\AuthMiddleware::class
    ]);
});

// --------------------------------------------------------------------------
// SSO 测试路由（可删除或保留）
// --------------------------------------------------------------------------
Route::group('sso/Test', function () {
    Route::get('Info', 'sso.Test.Info/Info');
});