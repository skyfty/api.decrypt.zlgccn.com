<?php
namespace app\middleware;

class cors
{
    public function handle($request, \Closure $next)
    {
        // 允许的前端域名（生产环境建议指定具体域名，不要用*）
        $origin = $request->header('origin', '*');
        $allowOrigins = [
            'http://localhost:4000',  // 本地开发环境
            // 'https://decrypt.zlgccn.com'   // 生产环境域名
        ];
        
        // 如果请求来源在允许列表中，则允许跨域
        if (in_array($origin, $allowOrigins) || env('APP_ENV') === 'development') {
            header("Access-Control-Allow-Origin: {$origin}");
        }
        
        // 允许的请求方法
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        // 允许的请求头（必须包含前端传递的自定义头，如access-token、refresh-token）
        header("Access-Control-Allow-Headers: Content-Type, access-token, refresh-token, X-Requested-With");
        // 允许前端读取的响应头
        header("Access-Control-Expose-Headers: Content-Length, Content-Type");
        // 允许携带Cookie（如果需要）
        header("Access-Control-Allow-Credentials: true");
        
        // 处理预检请求（OPTIONS）
        if ($request->method() === 'OPTIONS') {
            // 预检请求不需要返回具体内容
            return response('', 204);
        }
        
        return $next($request);
    }
}
    