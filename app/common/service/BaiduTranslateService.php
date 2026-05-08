<?php
// app/common/service/BaiduTranslateService.php

namespace app\common\service;

use think\facade\Log;

class BaiduTranslateService
{
    // 百度翻译 API 配置
    private $appid = '20251201002509752';
    private $secretKey = 'VLZ1rzUQhsTnhYfU1ZKF';
    private $apiUrl = 'https://fanyi-api.baidu.com/api/trans/vip/translate';

    /**
     * 翻译文本
     * @param string $text 要翻译的文本
     * @param string $from 源语言，默认 auto
     * @param string $to 目标语言，默认 en
     * @return array
     */
    public function translate($text = '测试', $from = 'auto', $to = 'en')
    {
        try {
            // 生成签名
            $salt = time();
            $sign = md5($this->appid . $text . $salt . $this->secretKey);

            // 准备请求参数
            $params = [
                'q'     => $text,
                'from'  => $from,
                'to'    => $to,
                'appid' => $this->appid,
                'salt'  => $salt,
                'sign'  => $sign
            ];

            // 发送请求
            $result = $this->httpPost($this->apiUrl, $params);

            // 解析响应
            $response = json_decode($result, true);

            if (isset($response['error_code'])) {
                Log::error('百度翻译API错误: ' . $response['error_code'] . ' - ' . ($response['error_msg'] ?? ''));

                // 处理特定的错误码
                if ($response['error_code'] == '52003') {
                    throw new \Exception('未授权用户，请检查APPID和密钥');
                }

                throw new \Exception('翻译失败: ' . ($response['error_msg'] ?? '未知错误'));
            }

            return $response;
            // return [
            //     'data'    => $response,
            //     'translated_text' => isset($response['trans_result'][0]['dst'])
            //         ? $response['trans_result'][0]['dst']
            //         : ''
            // ];
        } catch (\Exception $e) {
            Log::error('翻译请求失败: ' . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'code'    => 500
            ];
        }
    }

    /**
     * 发送 HTTP POST 请求
     * @param string $url 请求地址
     * @param array $data 请求数据
     * @return string
     */
    private function httpPost($url, $data)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // 设置 HTTP 头
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception('CURL错误: ' . curl_error($ch));
        }

        // curl_close($ch);

        return $response;
    }

    /**
     * 获取支持的语言列表
     * @return array
     */
    public function getSupportedLanguages()
    {
        return [
            // 自动检测
            'auto' => '自动检测',

            // 'ara'  => '阿拉伯语',
            'zh'   => '中文',
            'en'   => '英语',
            'cht'  => '繁体中文',
            'de'   => '德语',
            'fra'  => '法语',
            'ind'  => '印尼语',
            'it'   => '意大利语',
            'jp'   => '日语',
            'kor'  => '韩语',
            'pl'   => '波兰语',
            'pt'   => '葡萄牙语',
            'ru'   => '俄语',
            'spa'  => '西班牙语',
            'tur'  => '土耳其语',
            'vie'  => '越南语',
            // 'per'  => '波斯语',
            // 'th'   => '泰语',




        ];
    }
}
