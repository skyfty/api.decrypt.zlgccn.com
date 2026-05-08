<?php

namespace app\middleware;

class Maintenance
{
    public function handle($request, \Closure  $next)
    {
        // 可以从配置、数据库或环境变量读取是否维护中
        // $isMaintenance = config('app.maintenance_mode', false);
        $isMaintenance = env('MAINTENANCE_MODE', false);

        if ($isMaintenance) {
            return error( '系统正在升级维护中，请稍后再试', 503);
        }

        return  $next($request);
    }
}
