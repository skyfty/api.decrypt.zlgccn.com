<?php
// app/controller/v1/client/storyPoint/ProjectStoryLineController.php
namespace app\controller\v1\client\storyPoint;

use app\BaseController;
use app\model\Image;
use app\model\ProjectStoryLine\ProjectStoryLine;

class ProjectStoryLineController extends BaseController
{
    /**
     * 获取剧情点列表
     */
    public function index()
    {
        $projectId = $this->request->param('project_id/d', 0);

        $scale = (int) $this->request->param('scale', 100);
        if (!$projectId) {
            return json(['code' => 400, 'message' => '参数错误']);
        }
        // 限制scale范围在1-100
        if ($scale < 1 || $scale > 100) {
            return error('缩放比例必须在1-100之间', 400);
        }

        $query = ProjectStoryLine::with([
            'projectStoryLineCondition',
            'projectStoryLineAction.ProjectStoryLineActionAssignVariable',
        ])->where('project_id', $projectId)
            ->select();

        // 格式化数据
        $formattedList = [];
        foreach ($query as $projectLine) {
            $formattedList[] = $this->formatStoryPoint($projectLine, $scale);
        }
        if (empty($formattedList)) {
            return success($formattedList, '当前项目，尚未配置剧情.');
        } else {
            return success($formattedList, '请求成功');
        }
    }

    /**
     * 格式化剧情点数据
     */
    private function formatStoryPoint($projectLine, $scale)
    {
        $formatted = [
            'id' => $projectLine->id,
            'project_id' => $projectLine->project_id,
            'name' => $projectLine->name,
            'conditions' => [],
            'actions' => [
                [
                    'id' => 0,
                    'type' => 'palyRoleDialogue',
                    'project_story_line_id' => $projectLine->id,
                    'character_name' => $projectLine->character_name,
                    'character_image' => Image::getImageUrlById($projectLine->character_image, $scale),
                    'expression_image' => Image::getImageUrlById($projectLine->expression_image, $scale),
                    'dialogue' => $projectLine->dialogue,
                ]
            ]
        ];

        // 格式化条件
        foreach ($projectLine->projectStoryLineCondition as $condition) {
            $formatted['conditions'][] = [
                'id' => $condition->id,
                'variable_source' => $condition->variable_source,
                'variable_id' => $condition->variable_id,
                'operator' => $condition->operator,
                'variable_value' => $condition->variable_value
            ];
        }

        // 格式化处理
        foreach ($projectLine->projectStoryLineAction as $action) {
            $actionData = [
                'id' => $action->id,
                'type' => $action->action_type,
                'project_story_line_id' => $action->project_story_line_id,
            ];

            if ($action->action_type === 'assignVariable' && $action->ProjectStoryLineActionAssignVariable) {
                $actionData['variable_source'] = $action->ProjectStoryLineActionAssignVariable->variable_source;
                $actionData['variable_id'] = $action->ProjectStoryLineActionAssignVariable->variable_id;
                $actionData['assign_value'] = $action->ProjectStoryLineActionAssignVariable->assign_value;
                // $actionData['variable_operation_type'] = $action->ProjectStoryLineActionAssignVariable->variable_operation_type;
            }

            $formatted['actions'][] = $actionData;
        }

        return $formatted;
    }
}
