<?php

namespace app\utils;

class Result
{
    public static function success($data, $msg = 'success')
    {
        return json([
            'code' => 200,
            'msg'  => $msg,
            'data' => $data,
        ]);
    }

    public static function error($msg = 'error', $code = 400)
    {
        return json([
            'code' => $code,
            'msg'  => $msg,
            'data' => null,
        ]);
    }
}