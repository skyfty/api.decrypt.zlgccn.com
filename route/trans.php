<?php

use think\facade\Route;

// 翻译相关路由
Route::group('api/translate', function() {
    // 单条翻译
    Route::get('', 'app\controller\api\trans\Translate@translate');
    // 批量翻译
    Route::post('batch', 'app\controller\api\trans\Translate@batchTranslate');
    // 获取支持的语言
    Route::get('languages', 'app\controller\api\trans\Translate@languages');
});

// 翻译关键字
Route::group('v1/Dialogue', function() {
    // 翻译关键字
    Route::get('', 'app\controller\v1\editor\dialogue\Dialogue@index');
    Route::post('/saveSimple', 'app\controller\v1\editor\dialogue\Dialogue@saveSimple');
    Route::get('/exportCsv', 'app\controller\v1\editor\dialogue\Dialogue@exportCsv');
    Route::get('/getCsvContent', 'app\controller\v1\editor\dialogue\Dialogue@getCsvContent');
});