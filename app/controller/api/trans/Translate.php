<?php
// app/controller/api/Translate.php

namespace app\controller\api\trans;

use app\BaseController;
use app\common\service\BaiduTranslateService;
use think\facade\Request;

class Translate extends BaseController
{
    /**
     * 翻译接口
     * @return \think\Response
     */
    public function translate()
    {
        try {
            // 获取请求参数
            $text = Request::param('text');
            $from = Request::param('from', 'auto');
            $to = Request::param('to', 'en');
            
            // 验证参数
            if (empty($text)) {
                return error('翻译文本不能为空', 400 );
            }
            
            // 调用翻译服务
            $translateService = new BaiduTranslateService();
            $result = $translateService->translate($text, $from, $to);
            
            return success($result, '翻译成功');
            
        } catch (\Exception $e) {
            return error($e->getMessage(), 500 );
        }
    }
    
    /**
     * 获取支持的语言列表
     * @return \think\Response
     */
    public function languages()
    {
        $translateService = new BaiduTranslateService();
        $languages = $translateService->getSupportedLanguages();
        
        return success($languages, '翻译成功', 200, true);
    }
    
    /**
     * 批量翻译接口
     * @return \think\Response
     */
    public function batchTranslate()
    {
        try {
            $texts = Request::param('texts/a', []);
            $from = Request::param('from', 'auto');
            $to = Request::param('to', 'en');
            
            if (empty($texts) || !is_array($texts)) {
                return json([
                    'success' => false,
                    'error' => '翻译文本列表不能为空',
                    'code' => 400
                ]);
            }
            
            $translateService = new BaiduTranslateService();
            $results = [];
            
            foreach ($texts as $text) {
                $result = $translateService->translate($text, $from, $to);
                $results[] = $result ?? '';
            }
            
            return success( $results, '批量翻译成功');
            
        } catch (\Exception $e) {
            return error( $e->getMessage(), 500 );
        }
    }
}