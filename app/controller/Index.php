<?php
namespace app\controller;

use app\BaseController;
use think\facade\View; // 使用模板引擎
use think\facade\Db; // 使用Db数据

class Index extends BaseController
{
    public function index()
    {
        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 22px }</style><div style="padding: 24px 48px;"><p> 解密编辑器Api.</p></div>';
    }

    public function hello()
    {
        return 'hello,你好啊';
    }

    // public function index()
    // { 
    //     $query = Db::query('select * from itemsColumn');
    //     dump($query) ;
    // }
}
