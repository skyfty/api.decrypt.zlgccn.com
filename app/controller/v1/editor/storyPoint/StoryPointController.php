<?php
// app/controller/StoryPointController.php
namespace app\controller\v1\editor\storyPoint;

use app\BaseController;
use app\model\StoryPoint\StoryPoint;
use app\model\StoryPoint\StoryPointCondition;
use app\model\StoryPoint\StoryPointAction;
use app\model\StoryPoint\ActionAssignVariable;
use app\model\StoryPoint\ActionOperateButtonPoint;
use app\model\StoryPoint\ActionAnimationPlayMode;
use app\model\StoryPoint\ActionWeatherParame;
use app\model\StoryPoint\ActionRoleDialogue;
use app\model\StoryPoint\ActionChapter;
use app\model\StoryPoint\ActionSetAttribute;
use app\model\StoryPoint\RoomStoryPointCondition\ConditionStoryVariable;
use app\model\StoryPoint\RoomStoryPointCondition\ConditionAttribute;
use app\model\Trans\TranslationKeyword;
use think\facade\Db;
use think\facade\Validate;
use think\facade\Log; // 引入日志

class StoryPointController extends BaseController
{
    /**
     * 获取剧情点列表
     */
    public function index()
    {
        $roomId = $this->request->param('room_id/d', 0);
        $projectId = $this->request->param('project_id/d', 0);

        if (!$roomId && !$projectId) {
            return json(['code' => 400, 'message' => '参数错误']);
        }

        $query = StoryPoint::with([
            'conditions',
            'actions'
        ]);

        if ($roomId) {
            $query->where('room_id', $roomId);
        }
        $list = $query->select();

        // 格式化数据
        $formattedList = [];
        foreach ($list as $storyPoint) {
            $formattedList[] = $this->formatStoryPoint($storyPoint);
        }

        return success($formattedList);
    }

    /**
     * 创建剧情点
     */
    public function save()
    {
        $data = $this->request->post();

        // 验证基础数据
        $validate = Validate::rule([
            'room_id'    => 'require|number',
            'name'       => 'require|max:100',
            'conditions' => 'require|array',
            'actions'    => 'require|array'
        ]);

        if (!$validate->check($data)) {
            return error($validate->getError(), 500);
        }

        Db::startTrans();
        try {
            // 创建剧情点
            $storyPoint = StoryPoint::create([
                'room_id'     => $data['room_id'],
                'name'        => $data['name'],
                'description' => $data['description'] ?? '',
                'status'      => $data['status'] ?? 1,
            ]);

            // 创建条件
            $this->createConditions($storyPoint->id, $data['conditions']);

            // 创建处理
            $this->createActions($storyPoint->id, $data['actions'], $storyPoint);

            Db::commit();

            return success(true, '创建成功');
        } catch (\Exception $e) {
            Db::rollback();
            return error('创建失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新剧情点
     */
    public function update()
    {
        $data = $this->request->post();
        $id = $data['id'];
        $storyPoint = StoryPoint::find($id);
        if (!$storyPoint) {
            return error('剧情点不存在', 400);
        }

        Db::startTrans();
        try {
            // 更新基础信息
            if (isset($data['name'])) $storyPoint->name = $data['name'];
            if (isset($data['description'])) $storyPoint->description = $data['description'];
            if (isset($data['status'])) $storyPoint->status = $data['status'];
            $storyPoint->save();

            // 删除旧的条件和处理
            StoryPointCondition::where('story_point_id', $id)->delete();
            StoryPointAction::where('story_point_id', $id)->delete();

            // 创建新的条件和处理
            if (isset($data['conditions'])) {
                $this->createConditions($id, $data['conditions']);
            }

            if (isset($data['actions'])) {
                $this->createActions($id, $data['actions'], $storyPoint);
            }

            Db::commit();

            return success(true, '更新成功');
        } catch (\Exception $e) {
            Db::rollback();
            return error('更新失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 删除剧情点
     */
    public function delete()
    {
        $data = $this->request->param();

        $storyPoint = StoryPoint::find($data['id']);
        if (!$storyPoint) {
            return error('剧情点不存在或已被删除', 200);
        }

        try {
            $storyPoint->delete();
            return success(true, '删除成功');
        } catch (\Exception $e) {
            return error('删除失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 创建条件
     */
    private function createConditions($storyPointId, $conditions)
    {
        foreach ($conditions as $condition) {
            $conditionModel = StoryPointCondition::create([
                'story_point_id'  => $storyPointId,
                'type' => $condition['type']
            ]);
            
            if ($condition['type'] === 'storyVariable') {
                ConditionStoryVariable::create([
                    'condition_id'       => $conditionModel->id,
                    'variable_source' => $condition['param']['variable_source'],
                    'variable_id'    => $condition['param']['variable_id'],
                    'operator'    => $condition['param']['operator'],
                    'variable_value'    => $condition['param']['variable_value']
                ]);
            } elseif ($condition['type'] === 'attribute') {
                ConditionAttribute::create([
                    'condition_id'       => $conditionModel->id,
                    'key' => $condition['param']['key'],
                    'operator'    => $condition['param']['operator'],
                    'value'       => $condition['param']['value']
                ]);
            }
        }
    }

    /**
     * 创建处理
     */
    private function createActions($storyPointId, $actions, $storyPoint)
    {
        foreach ($actions as $index => $action) {
            // 创建基础处理
            $actionModel = StoryPointAction::create([
                'story_point_id' => $storyPointId,
                'action_order'   => $index,
                'type'    => $action['type']
            ]);

            // 根据类型创建具体处理
            if ($action['type'] === 'assignVariable') {
                ActionAssignVariable::create([
                    'action_id'       => $actionModel->id,
                    'variable_source' => $action['param']['variable_source'],
                    'variable_id'    => $action['param']['variable_id'],
                    'assign_value'    => $action['param']['assign_value']
                ]);
            } elseif ($action['type'] === 'operateButtonPoint') {
                ActionOperateButtonPoint::create([
                    'action_id'       => $actionModel->id,
                    'buttonPoint_id' => $action['param']['buttonPoint_id'],
                    'status'       => $action['param']['status']
                ]);
            } elseif ($action['type'] === 'animationPlayMode') {
                ActionAnimationPlayMode::create([
                    'action_id'       => $actionModel->id,
                    'buttonPoint_id' => $action['param']['buttonPoint_id'],
                    'animation_play_count'       => $action['param']['animation_play_count']
                ]);
            } elseif ($action['type'] === 'updateWeather') {
                ActionWeatherParame::create([
                    'action_id'       => $actionModel->id,
                    'weather_type' => $action['param']['weather_type'],
                    'intensity'       => $action['param']['intensity']
                ]);
            } elseif ($action['type'] === 'roleDialogue') {
                ActionRoleDialogue::create([
                    'action_id'       => $actionModel->id,
                    'character_name' => $action['param']['character_name'],
                    'character_image' => $action['param']['character_image'],
                    'expression_image' => $action['param']['expression_image'],
                    'dialogue' => $action['param']['dialogue'],
                ]);
            } elseif ($action['type'] === 'chapter') {
                ActionChapter::create([
                    'action_id'       => $actionModel->id,
                    'content' => $action['param']['content'],
                    'background' => $action['param']['background'],
                    'chapter_type' => $action['param']['chapter_type'],
                    'duration' => $action['param']['duration']
                ]);
            } elseif ($action['type'] === 'setAttribute') {
                ActionSetAttribute::create([
                    'action_id'       => $actionModel->id,
                    'attribute_id' => $action['param']['attribute_id'],
                    'value' => $action['param']['value'],
                ]);
            }

            // 创建所需条件关联
            if (!empty($action['requiredConditionIndexes'])) {
                $conditionIds = [];

                foreach ($action['requiredConditionIndexes'] as $index) {
                    if (isset($storyPoint->conditions[$index])) {
                        $conditionIds[] = $storyPoint->conditions[$index]->id;
                    }
                }

                if (!empty($conditionIds)) {
                    try {
                        // 使用 Eloquent 关系附加条件
                        $actionModel->requiredConditions()->attach($conditionIds);
                    } catch (\Exception $e) {
                        Log::error("关联条件失败: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * 格式化剧情点数据
     */
    public function formatStoryPoint($storyPoint)
    {
        // 基础字段格式化
        $formatted = [
            'id' => $storyPoint->id,
            'room_id' => $storyPoint->room_id,
            'name' => $storyPoint->name,
            'description' => $storyPoint->description,
            'status' => $storyPoint->status,
            'conditions' => $this->formatCondition($storyPoint->conditions),
            'actions' => []
        ];

        // 预缓存条件ID和索引的映射
        $conditionIds = $storyPoint->conditions->column('id');
        $conditionIdToIndex = array_flip($conditionIds);

        // 格式化动作列表
        foreach ($storyPoint->actions as $action) {
            $formatted['actions'][] = $this->formatAction($action, $conditionIdToIndex);
        }

        return $formatted;
    }

    /**
     * 格式化单个条件
     */
    private function formatCondition($conditions)
    {
        $formattedCondition = [];
        foreach ($conditions as $condition) {
            $formattedConditionData = [
                'id' => $condition->id,
                'story_point_id' => $condition->story_point_id,
                'type' => $condition->type,
                'param' => [],
            ];

            // 定义condition类型与格式化规则的映射表
            $conditionFormatMap = [
                'storyVariable' => [
                    'relation' => 'storyVariable',
                    'fields' => ['variable_source', 'variable_id', 'operator', 'variable_value']
                ],
                'attribute' => [
                    'relation' => 'attribute',
                    'fields' => ['key', 'operator', 'operator', 'value']
                ]
            ];

            // 根据类型格式化参数
            if (isset($conditionFormatMap[$condition->type])) {
                $config = $conditionFormatMap[$condition->type];
                $relationData = isset($condition->{$config['relation']}) ? $condition->{$config['relation']} : null;

                // 空值保护：关联数据不存在时跳过
                if ($relationData) {
                    foreach ($config['fields'] as $field) {
                        $formattedConditionData['param'][$field] = isset($relationData->{$field}) ? $relationData->{$field} : null;
                    }
                }
            }
            $formattedCondition[] = $formattedConditionData;
        }

        return $formattedCondition;
    }

    /**
     * 格式化单个动作
     */
    private function formatAction($action, array $conditionIdToIndex)
    {
        // 动作基础信息
        $actionData = [
            'id' => $action->id,
            'story_point_id' => $action->story_point_id,
            'type' => $action->type,
            'requiredConditionIndexes' => $this->getRequiredConditionIndexes($action->requiredConditions, $conditionIdToIndex),
            'param' => []
        ];

        // 定义类型与格式化规则的映射表
        $actionFormatMap = [
            'assignVariable' => [
                'relation' => 'assignVariable',
                'fields' => ['variable_source', 'variable_id', 'assign_value', 'variable_operation_type']
            ],
            'operateButtonPoint' => [
                'relation' => 'operateButtonPoint',
                'fields' => ['buttonPoint_id', 'status']
            ],
            'animationPlayMode' => [
                'relation' => 'animationPlayMode',
                'fields' => ['buttonPoint_id', 'animation_play_count']
            ],
            'updateWeather' => [
                'relation' => 'updateWeather',
                'fields' => ['weather_type', 'intensity']
            ],
            'roleDialogue' => [
                'relation' => 'roleDialogue',
                'fields' => ['character_name', 'character_image', 'expression_image', 'dialogue', 'has_options']
            ],
            'chapter' => [
                'relation' => 'chapter',
                'fields' => ['content', 'background', 'chapter_type', 'duration']
            ],
            'setAttribute' => [
                'relation' => 'setAttribute',
                'fields' => ['attribute_id', 'value', 'operation_type']
            ]
        ];

        // 根据类型格式化参数
        if (isset($actionFormatMap[$action->type])) {
            $config = $actionFormatMap[$action->type];
            $relationData = isset($action->{$config['relation']}) ? $action->{$config['relation']} : null;

            // 空值保护：关联数据不存在时跳过
            if ($relationData) {
                foreach ($config['fields'] as $field) {
                    $actionData['param'][$field] = isset($relationData->{$field}) ? $relationData->{$field} : null;
                }
            }
        }
        if($actionData['type'] === 'roleDialogue' && isset($actionData['param']['dialogue'])){
            $actionData['param']['dialogue_Chinese'] = TranslationKeyword::queryKeyByName($actionData['param']['dialogue']);
        }

        return $actionData;
    }

    /**
     * 获取动作依赖的条件索引
     */
    private function getRequiredConditionIndexes($requiredConditions, array $conditionIdToIndex)
    {
        $indexes = [];
        $requiredConditions->each(function ($condition) use (&$indexes, $conditionIdToIndex) {
            // 安全获取索引，不存在时返回-1（或根据业务需求调整）
            $indexes[] = isset($conditionIdToIndex[$condition->id]) ? $conditionIdToIndex[$condition->id] : -1;
        });
        return $indexes;
    }
}
