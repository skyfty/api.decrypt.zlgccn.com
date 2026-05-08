<?php
// 全局中间件定义文件
return [
    // 全局请求缓存
    // \think\middleware\CheckRequestCache::class,
    // 多语言加载
    // \think\middleware\LoadLangPack::class,
    // Session初始化
    // \think\middleware\SessionInit::
    // 跨域解决
    // \think\middleware\AllowCrossDomain::class,
    // 全局中间件
    \app\middleware\cors::class,  // 放在最前面
     
    // 单个中间件别名
    // 'cors' => \app\middleware\Cors::class,
    // 'auth' => \app\middleware\AuthMiddleware::class,
    // // 组合中间件
    // 'api' => [
    //     \app\middleware\Cors::class,
    //     \app\middleware\AuthMiddleware::class
    // ]
];
