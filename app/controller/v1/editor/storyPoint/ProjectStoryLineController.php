<?php
// app/controller/v1/editor/storyPoint/ProjectStoryLineController.php
namespace app\controller\v1\editor\storyPoint;

use app\BaseController;
use app\model\ProjectStoryLine\ProjectStoryLine;
use app\model\ProjectStoryLine\ProjectStoryLineCondition;
use app\model\ProjectStoryLine\ProjectStoryLineAction;
use app\model\ProjectStoryLine\ProjectStoryLineActionAssignVariable;
use think\facade\Db;

class ProjectStoryLineController extends BaseController
{
    /**
     * 获取剧情点列表
     */
    public function index()
    {
        $projectId = $this->request->param('project_id/d', 0);

        if (!$projectId) {
            return json(['code' => 400, 'message' => '参数错误']);
        }

        $query = ProjectStoryLine::with([
            'projectStoryLineCondition',
            'projectStoryLineAction.ProjectStoryLineActionAssignVariable',
        ])->where('project_id', $projectId)
            ->select();

        // 格式化数据
        $formattedList = [];
        foreach ($query as $projectLine) {
            $formattedList[] = $this->formatStoryPoint($projectLine);
        }

        return json([
            'code' => 200,
            'data' => $formattedList
        ]);
    }

    /**
     * 创建剧情点
     */
    public function create()
    {
        $data = $this->request->post();

        // 验证必要参数
        $requiredFields = ['project_id', 'name'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return json(['code' => 400, 'message' => "缺少必要参数: {$field}"]);
            }
        }

        try {
            Db::startTrans();

            // 创建主剧情点信息
            $storyLine = new ProjectStoryLine();
            $storyLine->project_id = $data['project_id'];
            $storyLine->name = $data['name'];
            $storyLine->character_name = $data['character_name'] ?? '';
            $storyLine->character_image = $data['character_image'] ?? '';
            $storyLine->expression_image = $data['expression_image'] ?? '';
            $storyLine->dialogue = $data['dialogue'] ?? '';

            if (!$storyLine->save()) {
                throw new \Exception('创建剧情点失败');
            }

            $storyLineId = $storyLine->id;

            // 创建条件
            if (isset($data['conditions']) && is_array($data['conditions'])) {
                $this->createConditions($storyLineId, $data['conditions']);
            }

            // 创建动作
            if (isset($data['actions']) && is_array($data['actions'])) {
                $this->createActions($storyLineId, $data['actions']);
            }

            Db::commit();

            // 返回创建后的完整数据
            $createdStoryLine = ProjectStoryLine::with([
                'projectStoryLineCondition',
                'projectStoryLineAction.ProjectStoryLineActionAssignVariable',
            ])->find($storyLineId);

            return success($this->formatStoryPoint($createdStoryLine), '创建成功');

        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 500, 'message' => '创建失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 更新剧情点
     */
    public function update()
    {
        $data = $this->request->post();

        // 验证必要参数
        $requiredFields = ['id', 'project_id', 'name'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return json(['code' => 400, 'message' => "缺少必要参数: {$field}"]);
            }
        }

        try {
            Db::startTrans();

            // 更新主剧情点信息
            $storyLine = ProjectStoryLine::find($data['id']);
            if (!$storyLine) {
                return json(['code' => 404, 'message' => '剧情点不存在']);
            }

            // 检查项目ID是否匹配
            if ($storyLine->project_id != $data['project_id']) {
                return json(['code' => 403, 'message' => '无权操作此项目']);
            }

            // 更新基本信息
            $storyLine->name = $data['name'];
            $storyLine->character_name = $data['character_name'] ?? '';
            $storyLine->character_image = $data['character_image'] ?? '';
            $storyLine->expression_image = $data['expression_image'] ?? '';
            $storyLine->dialogue = $data['dialogue'] ?? '';

            if (!$storyLine->save()) {
                throw new \Exception('更新剧情点失败');
            }

            // 更新条件
            if (isset($data['conditions']) && is_array($data['conditions'])) {
                $this->updateConditions($storyLine->id, $data['conditions']);
            }

            // 更新动作
            if (isset($data['actions']) && is_array($data['actions'])) {
                $this->updateActions($storyLine->id, $data['actions']);
            }

            Db::commit();

            // 返回更新后的完整数据
            $updatedStoryLine = ProjectStoryLine::with([
                'projectStoryLineCondition',
                'projectStoryLineAction.ProjectStoryLineActionAssignVariable',
            ])->find($data['id']);

            return success($this->formatStoryPoint($updatedStoryLine), '更新成功');

        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 500, 'message' => '更新失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 创建条件
     */
    private function createConditions($storyLineId, $conditions)
    {
        foreach ($conditions as $conditionData) {
            if (empty($conditionData['variable_source']) || empty($conditionData['variable_id'])) {
                continue; // 跳过无效条件
            }

            $condition = new ProjectStoryLineCondition();
            $condition->project_story_line_id = $storyLineId;
            $condition->variable_source = $conditionData['variable_source'];
            $condition->variable_id = $conditionData['variable_id'];
            $condition->operator = $conditionData['operator'] ?? '=';
            $condition->variable_value = $conditionData['variable_value'] ?? '';
            
            if (!$condition->save()) {
                throw new \Exception('创建条件失败');
            }
        }
    }

    /**
     * 创建动作
     */
    private function createActions($storyLineId, $actions)
    {
        foreach ($actions as $actionData) {
            if (empty($actionData['type'])) {
                continue; // 跳过无效动作
            }

            $action = new ProjectStoryLineAction();
            $action->project_story_line_id = $storyLineId;
            $action->action_type = $actionData['type'];
            
            if (!$action->save()) {
                throw new \Exception('创建动作失败');
            }

            // 处理赋值变量数据
            $this->createAssignVariable($action->id, $actionData);
        }
    }

    /**
     * 创建赋值变量数据
     */
    private function createAssignVariable($actionId, $actionData)
    {
        if ($actionData['type'] !== 'assignVariable') {
            return;
        }

        if (isset($actionData['variable_source']) && isset($actionData['variable_id'])) {
            $assignVariable = new ProjectStoryLineActionAssignVariable();
            $assignVariable->project_story_line_action_id = $actionId;
            $assignVariable->variable_source = $actionData['variable_source'];
            $assignVariable->variable_id = $actionData['variable_id'];
            $assignVariable->assign_value = $actionData['assign_value'] ?? '';
            $assignVariable->variable_operation_type = $actionData['variable_operation_type'] ?? 'set';
            
            if (!$assignVariable->save()) {
                throw new \Exception('创建赋值变量数据失败');
            }
        }
    }

    /**
     * 更新条件 - 支持新增、更新、删除
     */
    private function updateConditions($storyLineId, $conditions)
    {
        $submittedIds = []; // 记录前端提交的条件ID

        foreach ($conditions as $conditionData) {
            if (empty($conditionData['variable_source']) || empty($conditionData['variable_id'])) {
                continue; // 跳过无效条件
            }

            // 判断是更新还是新增
            if (!empty($conditionData['id'])) {
                // 更新现有条件
                $condition = ProjectStoryLineCondition::find($conditionData['id']);
                if ($condition && $condition->project_story_line_id == $storyLineId) {
                    $condition->variable_source = $conditionData['variable_source'];
                    $condition->variable_id = $conditionData['variable_id'];
                    $condition->operator = $conditionData['operator'] ?? '=';
                    $condition->variable_value = $conditionData['variable_value'] ?? '';

                    if (!$condition->save()) {
                        throw new \Exception('更新条件失败');
                    }
                    $submittedIds[] = $condition->id;
                }
            } else {
                // 新增条件
                $condition = new ProjectStoryLineCondition();
                $condition->project_story_line_id = $storyLineId;
                $condition->variable_source = $conditionData['variable_source'];
                $condition->variable_id = $conditionData['variable_id'];
                $condition->operator = $conditionData['operator'] ?? '=';
                $condition->variable_value = $conditionData['variable_value'] ?? '';

                if (!$condition->save()) {
                    throw new \Exception('新增条件失败');
                }
                $submittedIds[] = $condition->id;
            }
        }

        // 删除未在本次提交中的条件（被前端删除了的）
        if (!empty($submittedIds)) {
            ProjectStoryLineCondition::where('project_story_line_id', $storyLineId)
                ->whereNotIn('id', $submittedIds)
                ->delete();
        } else {
            // 如果前端没有提交任何条件，则清空所有条件
            ProjectStoryLineCondition::where('project_story_line_id', $storyLineId)->delete();
        }
    }

    /**
     * 更新动作 - 支持新增、更新、删除
     */
    private function updateActions($storyLineId, $actions)
    {
        $submittedActionIds = []; // 记录前端提交的动ID

        foreach ($actions as $actionData) {
            if (empty($actionData['type'])) {
                continue; // 跳过无效动作
            }

            // 判断是更新还是新增
            if (!empty($actionData['id'])) {
                // 更新现有动作
                $action = ProjectStoryLineAction::find($actionData['id']);
                if ($action && $action->project_story_line_id == $storyLineId) {
                    $action->action_type = $actionData['type'];

                    if (!$action->save()) {
                        throw new \Exception('更新动作失败');
                    }

                    // 处理赋值变量数据
                    $this->updateAssignVariable($action->id, $actionData);
                    $submittedActionIds[] = $action->id;
                }
            } else {
                // 新增动作
                $action = new ProjectStoryLineAction();
                $action->project_story_line_id = $storyLineId;
                $action->action_type = $actionData['type'];

                if (!$action->save()) {
                    throw new \Exception('新增动作失败');
                }

                // 处理赋值变量数据
                $this->updateAssignVariable($action->id, $actionData);
                $submittedActionIds[] = $action->id;
            }
        }

        // 删除未在本次提交中的动作及其相关数据
        if (!empty($submittedActionIds)) {
            $actionsToDelete = ProjectStoryLineAction::where('project_story_line_id', $storyLineId)
                ->whereNotIn('id', $submittedActionIds)
                ->select();
            
            foreach ($actionsToDelete as $action) {
                // 删除赋值变量数据
                if ($action->action_type === 'assignVariable') {
                    ProjectStoryLineActionAssignVariable::where('project_story_line_action_id', $action->id)->delete();
                }
            }
            
            ProjectStoryLineAction::where('project_story_line_id', $storyLineId)
                ->whereNotIn('id', $submittedActionIds)
                ->delete();
        } else {
            // 如果前端没有提交任何动作，则清空所有动作
            $existingActions = ProjectStoryLineAction::where('project_story_line_id', $storyLineId)->select();
            foreach ($existingActions as $action) {
                if ($action->action_type === 'assignVariable') {
                    ProjectStoryLineActionAssignVariable::where('project_story_line_action_id', $action->id)->delete();
                }
            }
            ProjectStoryLineAction::where('project_story_line_id', $storyLineId)->delete();
        }
    }

    /**
     * 更新赋值变量数据
     */
    private function updateAssignVariable($actionId, $actionData)
    {
        if ($actionData['type'] !== 'assignVariable') {
            return;
        }

        // 查找现有的赋值变量数据
        $existingAssign = ProjectStoryLineActionAssignVariable::where('project_story_line_action_id', $actionId)->find();

        if ($existingAssign) {
            // 更新现有数据
            if (isset($actionData['variable_source'])) {
                $existingAssign->variable_source = $actionData['variable_source'];
            }
            if (isset($actionData['variable_id'])) {
                $existingAssign->variable_id = $actionData['variable_id'];
            }
            if (isset($actionData['assign_value'])) {
                $existingAssign->assign_value = $actionData['assign_value'];
            }
            if (isset($actionData['variable_operation_type'])) {
                $existingAssign->variable_operation_type = $actionData['variable_operation_type'];
            }
            $existingAssign->save();
        } elseif (isset($actionData['variable_source']) && isset($actionData['variable_id'])) {
            // 新增赋值变量数据
            $assignVariable = new ProjectStoryLineActionAssignVariable();
            $assignVariable->project_story_line_action_id = $actionId;
            $assignVariable->variable_source = $actionData['variable_source'];
            $assignVariable->variable_id = $actionData['variable_id'];
            $assignVariable->assign_value = $actionData['assign_value'] ?? '';
            $assignVariable->variable_operation_type = $actionData['variable_operation_type'] ?? 'set';
            $assignVariable->save();
        }
    }

    /**
     * 格式化剧情点数据
     */
    private function formatStoryPoint($projectLine)
    {
        $formatted = [
            'id' => $projectLine->id,
            'project_id' => $projectLine->project_id,
            'name' => $projectLine->name,
            'character_name' => $projectLine->character_name,
            'character_image' => $projectLine->character_image,
            'expression_image' => $projectLine->expression_image,
            'dialogue' => $projectLine->dialogue,
            'conditions' => [],
            'actions' => []
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
                $actionData['variable_operation_type'] = $action->ProjectStoryLineActionAssignVariable->variable_operation_type;
            }

            $formatted['actions'][] = $actionData;
        }

        return $formatted;
    }
}