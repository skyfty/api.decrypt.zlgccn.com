<?php
// app/controller/v1/client/storyPoint/TranslateStoryLineDialogue.php
namespace app\controller\v1\client\storyPoint;

use app\BaseController;
use app\model\ProjectStoryLine\ProjectStoryLine;
use app\common\service\BaiduTranslateService;

class TranslateStoryLineDialogue extends BaseController
{
    /**
     * 获取剧情点列表并翻译成多种语言，直接返回CSV格式
     */
    public function index()
    {
        try {
            $projectId = $this->request->param('project_id/d', 0);
            if (!$projectId) {
                return error('参数错误', 400);
            }

            // 查询剧情线数据并按顺序排序
            $storyLines = ProjectStoryLine::where('project_id', $projectId)
                ->order('id', 'asc')
                ->select();

            if ($storyLines->isEmpty()) {
                return error('当前项目尚未配置剧情', 404);
            }

            // 获取多语言翻译结果
            $translatedData = $this->getMultiLanguageTranslationsForStoryLines($storyLines);

            // 生成CSV内容
            $csvContent = $this->generateCSV($translatedData);

            // 设置响应头，直接返回CSV文件
            return $this->csvResponse($csvContent, 'storyline_translations.csv');
        } catch (\Exception $e) {
            \think\facade\Log::error('获取剧情线对话失败: ' . $e->getMessage());
            return error($e->getMessage(), 500);
        }
    }

    /**
     * 获取多语言翻译结果
     */
    private function getMultiLanguageTranslationsForStoryLines($storyLines)
    {
        $translateService = new BaiduTranslateService();
        $translations = [];
        
        // 目标语言列表（排除auto）
        $targetLanguages = array_diff_key($translateService->getSupportedLanguages(), ['auto' => '自动检测']);

        // 为每个剧情点获取翻译
        foreach ($storyLines as $storyLine) {
            $dialogue = trim($storyLine->dialogue ?? '');
            if (empty($dialogue)) {
                continue;
            }

            $rowData = [
                'name' => 'translateStoryLineDialogue_' . $storyLine->id,
                'Type' => 'Text',
                // 'original' => $dialogue,
                'zh' => $dialogue, // 原始中文
            ];

            // 翻译到各种语言
            foreach ($targetLanguages as $langCode => $langName) {
                try {
                    $result = $translateService->translate($dialogue, 'auto', $langCode);
                    
                    // 解析翻译结果
                    if (isset($result['trans_result']) && is_array($result['trans_result'])) {
                        // 如果是百度翻译API的标准格式，提取所有翻译文本
                        $translatedText = '';
                        foreach ($result['trans_result'] as $item) {
                            $translatedText .= $item['dst'] . "\n";
                        }
                        $rowData[$langCode] = rtrim($translatedText, "\n");
                    } else {
                        // 如果返回的是简单字符串
                        $rowData[$langCode] = is_string($result) ? $result : 'INVALID_TO_PARAM';
                    }

                    // 避免API调用过于频繁
                    usleep(100000); // 0.1秒延迟

                } catch (\Exception $e) {
                    \think\facade\Log::error("翻译失败 [剧情点: {$rowData['name']}, 语言: {$langName}]: " . $e->getMessage());
                    $rowData[$langCode] = '';
                }
            }

            $translations[] = $rowData;
        }

        return $translations;
    }

    /**
     * 生成CSV内容
     */
    private function generateCSV($data)
    {
        if (empty($data)) {
            return '';
        }

        // CSV列定义
        $columns = [
            'name'      => 'Key',
            'Type'      => 'Type',
            'Desc'      => 'Desc',
            'zh'        => 'Chinese',
            'en'        => 'English',
            'cht'       => 'Chinese_Taiwan',
            'de'        => 'German',
            'fra'       => 'French',
            'ind'       => 'Indonesian',
            'it'        => 'Italian',
            'jp'        => 'Japanese',
            'kor'       => 'Korean',
            'pl'        => 'Polish',
            'pt'        => 'Portuguese',
            'ru'        => 'Russian',
            'spa'       => 'Spanish',
            'tur'       => 'Turkish',
            'vie'       => 'Vietnamese'
        ];

        // 输出CSV标题行
        $csvLines = [];
        $csvLines[] = $this->csvEscapeRow(array_values($columns));

        // 输出数据行
        foreach ($data as $row) {
            $csvRow = [];
            foreach (array_keys($columns) as $column) {
                $value = $row[$column] ?? '';
                $csvRow[] = $value;
            }
            $csvLines[] = $this->csvEscapeRow($csvRow);
        }

        // 添加BOM头解决中文乱码问题
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        return $bom . implode("\n", $csvLines);
    }

    /**
     * CSV转义处理
     */
    private function csvEscapeRow($row)
    {
        $escaped = [];
        foreach ($row as $value) {
            // 如果包含逗号、双引号或换行符，需要用双引号包裹
            if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
                $value = '"' . str_replace('"', '""', $value) . '"';
            }
            $escaped[] = $value;
        }
        return implode(',', $escaped);
    }

    /**
     * 返回CSV响应
     */
    private function csvResponse($csvContent, $filename)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csvContent;
        exit;
    }

    /**
     * 预览接口（可选，用于前端预览）
     */
    public function preview()
    {
        try {
            $projectId = $this->request->param('project_id/d', 0);
            if (!$projectId) {
                return error('参数错误', 400);
            }

            // 查询剧情线数据
            $storyLines = ProjectStoryLine::where('project_id', $projectId)
                ->order('id', 'asc')
                ->select();

            if ($storyLines->isEmpty()) {
                return success([], '当前项目尚未配置剧情');
            }

            // 获取多语言翻译结果
            $translatedData = $this->getMultiLanguageTranslationsForStoryLines($storyLines);

            return success([
                'total' => count($translatedData),
                'columns' => [
                    'name'      => 'Key',
                    'Type'      => 'Type',
                    'Desc'      => 'Desc',
                    'zh'        => 'Chinese',
                    'en'        => 'English',
                    'cht'       => 'Chinese_Taiwan',
                    'de'        => 'German',
                    'fra'       => 'French',
                    'ind'       => 'Indonesian',
                    'it'        => 'Italian',
                    'jp'        => 'Japanese',
                    'kor'       => 'Korean',
                    'pl'        => 'Polish',
                    'pt'        => 'Portuguese',
                    'ru'        => 'Russian',
                    'spa'       => 'Spanish',
                    'tur'       => 'Turkish',
                    'vie'       => 'Vietnamese'
                ],
                'data' => $translatedData
            ], '翻译预览');
        } catch (\Exception $e) {
            \think\facade\Log::error('预览翻译失败: ' . $e->getMessage());
            return error($e->getMessage(), 500);
        }
    }
}
