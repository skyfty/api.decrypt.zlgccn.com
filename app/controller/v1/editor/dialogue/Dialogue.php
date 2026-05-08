<?php
namespace app\controller\v1\editor\dialogue;

use app\BaseController;
use app\model\Trans\TranslationKeyword;

class Dialogue extends BaseController
{
    /**
     * 获取翻译关键字列表
     */
    public function index()
    {
        $project_id = $this->request->param('project_id/d', 0);

        if (!$project_id) {
            return json(['code' => 400, 'message' => '参数错误']);
        }

        $data = TranslationKeyword::where('project_id', $project_id)
            ->select();

        return success($data, '获取成功');
    }


    /**
     * 更新 / 保存翻译关键字
     * 
     */
    public function saveSimple()
    {
        $data = $this->request->post();
        
        // 验证必要参数
        if (empty($data['project_id'])) {
            return json(['code' => 400, 'message' => 'project_id不能为空']);
        }
        
        try {
            if (!empty($data['id'])) {
                // 更新操作
                $keyword = TranslationKeyword::find($data['id']);
                if (!$keyword) {
                    return json(['code' => 404, 'message' => '记录不存在']);
                }
                $data['update_time'] = date('Y-m-d H:i:s');
                $result = $keyword->save($data);
            } else {
                // 创建操作
                unset($data['id']);
                $data['create_time'] = date('Y-m-d H:i:s');
                $data['update_time'] = date('Y-m-d H:i:s');
                $keyword = new TranslationKeyword;
                $result = $keyword->save($data);
            }
            
            if ($result) {
                return success( $keyword, !empty($data['id']) ? '更新成功' : '创建成功' );
            } else {
                return error('操作失败, 500');
            }
            
        } catch (\Exception $e) {
            return error('操作失败：' . $e->getMessage(),  500);
        }
    }

     /**
     * 导出翻译关键字数据为CSV文件
     */
    public function exportCsv()
    {
        $project_id = $this->request->param('project_id/d', 0);

        if (!$project_id) {
            return json(['code' => 400, 'message' => '参数错误']);
        }

        try {
            // 获取数据
            $data = TranslationKeyword::where('project_id', $project_id)
                ->select();

            if (empty($data)) {
                return json(['code' => 404, 'message' => '没有找到相关数据']);
            }

            // 定义CSV表头（根据您的示例数据结构）
            $headers = [
                'Key',
                'Type',
                'Desc',
                'Chinese',
                'English',
                'Chinese_Taiwan',
                'German',
                'French',
                'Indonesian',
                'Italian',
                'Japanese',
                'Korean',
                'Polish',
                'Portuguese',
                'Russian',
                'Spanish',
                'Turkish',
                'Vietnamese'
            ];

            // 设置HTTP头信息用于文件下载
            $filename = 'translation_keywords_' . $project_id . '_' . date('YmdHis') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            // 输出到浏览器
            $output = fopen('php://output', 'w');
            
            // 添加BOM头，解决Excel中文乱码问题
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // 写入表头
            fputcsv($output, $headers);
            
            // 写入数据行
            foreach ($data as $row) {
                $csvRow = [];
                foreach ($headers as $header) {
                    // 处理null值和特殊值
                    $value = $row[$header] ?? '';
                    if ($value === null || $value === 'INVALID_TO_PARAM') {
                        $value = '';
                    }
                    // 处理包含换行符等特殊字符的情况
                    $value = str_replace(["\r\n", "\n", "\r"], ' ', $value);
                    $csvRow[] = $value;
                }
                fputcsv($output, $csvRow);
            }
            
            fclose($output);
            exit;
            
        } catch (\Exception $e) {
            return json([
                'code' => 500, 
                'message' => '导出失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 另一种实现方式：返回CSV文件内容（不直接下载，可用于API调用）
     */
    public function getCsvContent()
    {
        $project_id = $this->request->param('project_id/d', 0);

        if (!$project_id) {
            return json(['code' => 400, 'message' => '参数错误']);
        }

        try {
            // 获取数据
            $data = TranslationKeyword::where('project_id', $project_id)
                ->select();

            if (empty($data)) {
                return json(['code' => 404, 'message' => '没有找到相关数据']);
            }

            // 定义CSV表头
            $headers = [
                'Key',
                'Type',
                'Desc',
                'Chinese',
                'English',
                'Chinese_Taiwan',
                'German',
                'French',
                'Indonesian',
                'Italian',
                'Japanese',
                'Korean',
                'Polish',
                'Portuguese',
                'Russian',
                'Spanish',
                'Turkish',
                'Vietnamese'
            ];

            // 生成CSV内容
            $csvContent = '';
            
            // 添加BOM头
            $csvContent .= "\xEF\xBB\xBF";
            
            // 写入表头
            $csvContent .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $headers)) . "\n";
            
            // 写入数据行
            foreach ($data as $row) {
                $csvRow = [];
                foreach ($headers as $header) {
                    $value = $row[$header] ?? '';
                    if ($value === null || $value === 'INVALID_TO_PARAM') {
                        $value = '';
                    }
                    // 转义引号并添加引号包围
                    $value = '"' . str_replace('"', '""', $value) . '"';
                    $csvRow[] = $value;
                }
                $csvContent .= implode(',', $csvRow) . "\n";
            }

            return success([
                'filename' => 'translation_keywords_' . $project_id . '_' . date('YmdHis') . '.csv',
                'content' => $csvContent,
                'size' => strlen($csvContent)
            ], '获取CSV内容成功');
            
        } catch (\Exception $e) {
            return json([
                'code' => 500, 
                'message' => '生成CSV失败：' . $e->getMessage()
            ]);
        }
    }


}