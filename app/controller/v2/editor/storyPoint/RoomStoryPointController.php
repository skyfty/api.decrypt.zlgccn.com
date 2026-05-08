<?php

namespace app\controller\v2\editor\storyPoint;

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
use app\model\optionGroup\roomOptionGroup\RoomOptionGroup;
use think\facade\Db;
use think\facade\Validate;
use think\facade\Log;

class RoomStoryPointController
{
    /**
     * 获取剧情点列表
     */
    public function index()
    {
        $params = request()->param();

        // 验证基础数据
        $validate = Validate::rule([
            'room_id'    => 'require',
        ]);

        if (!$validate->check($params)) {
            return error($validate->getError(), 500);
        }

        $queryData = StoryPoint::with([
            'conditions',
            'actions'
        ])->where('room_id', $params['room_id'])->select();

        // 格式化数据
        $formattedList = [];
        foreach ($queryData as $storyPoint) {
            $formattedList[] = $this->formatStoryPoint($storyPoint);
        }
        return success($formattedList);
    }

    /**
     * 删除剧情点
     */
    public function delete()
    {
        $data = request()->param();

        // 验证基础数据
        $validate = Validate::rule([
            'id'    => 'require',
        ]);

        if (!$validate->check($data)) {
            return error($validate->getError(), 500);
        }

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
     * 保存剧情点
     */
    public function save()
    {
        $params = request()->post();
        $validate = Validate::rule([
            'room_id'    => 'require|number',
            'name'       => 'require|max:100',
            'conditions' => 'require|array',
            'actions'    => 'require|array'
        ]);
        if (!$validate->check($params)) {
            return error($validate->getError(), 500);
        }

        Db::startTrans();
        try {
            $storyPoint = empty($params['id'])
                ? new StoryPoint()
                : StoryPoint::find($params['id']);
            $storyPoint->room_id = $params['room_id'];
            $storyPoint->name = $params['name'];
            $storyPoint->description = $params['description'];
            $storyPoint->status = $params['status'];
            $storyPoint->save();

            // 保存条件
            $this->saveConditions($storyPoint->id, $params['conditions'] ?? []);

            // 创建处理
            $this->saveActions($storyPoint->id, $params['actions'] ?? [], $storyPoint);

            Db::commit();

            return success(true, '保存成功');
        } catch (\Exception $e) {
            Db::rollback();
            return error('保存失败: ' . $e->getMessage(), 500);
        }
    }

    // 保存条件
    private function saveConditions($story_point_id, $conditions)
    {
        try {
            $conditionIds = [];
            foreach ($conditions as $conditionData) {
                $condition = empty($conditionData['id'])
                    ? new StoryPointCondition()
                    : StoryPointCondition::find($conditionData['id']);


                // 处理类型修改的情况
                if (!empty($conditionData['id'])) {
                    if ($condition->type !== $conditionData['type']) {
                        switch ($condition->type) {
                            case 'storyVariable':
                                ConditionStoryVariable::where('condition_id', $conditionData['id'])->delete();
                                break;
                            case 'attribute':
                                ConditionAttribute::where('condition_id', $conditionData['id'])->delete();
                                break;
                        }
                    }
                }

                $condition->story_point_id = $story_point_id;
                $condition->type = $conditionData['type'];
                $condition->save();

                $conditionIds[] = $condition->id;
                // 保存条件参数
                $this->saveConditionParam(
                    $conditionData,
                    $condition
                );
            }

            // 删除未提及的选项
            StoryPointCondition::where('story_point_id', $story_point_id)
                ->whereNotIn('id', $conditionIds)
                ->delete();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    // 保存条件参数
    public function saveConditionParam($conditionData, $condition)
    {
        $typeConfig = [
            'storyVariable' => [
                'class'  => ConditionStoryVariable::class,
                'fields' => ['variable_source', 'variable_id', 'operator', 'variable_value']
            ],
            'attribute' => [
                'class'  => ConditionAttribute::class,
                'fields' => ['key', 'operator', 'value']
            ],
        ];

        $type = $condition->type;

        if (!isset($typeConfig[$type])) {
            throw new \InvalidArgumentException("Unsupported condition type: {$type}");
        }

        $config = $typeConfig[$type];
        $class = $config['class'];
        $fields = $config['fields'];

        try {
            $model = null;
            if (!empty($conditionData['id'])) {
                $model = $class::where('condition_id', $conditionData['id'])->find();
            }
            $model = $model ?? new $class();

            // 设置关联 ID
            $model->condition_id = $condition->id;

            // 赋值参数字段
            $paramData = $conditionData['param'] ?? [];
            foreach ($fields as $field) {
                $model->{$field} = $paramData[$field];
            }

            // 保存
            $model->save();
        } catch (\Exception $e) {
            // 保留原始异常信息
            throw new \Exception("Failed to save condition param for type {$type}: " . $e->getMessage(), 0, $e);
        }
    }

    // 保存处理
    private function saveActions($story_point_id, $actions, $storyPoint)
    {
        try {
            $actionIds = [];
            foreach ($actions as $action_order => $actionData) {
                $action = empty($actionData['id'])
                    ? new StoryPointAction()
                    : StoryPointAction::find($actionData['id']);

                // 处理类型修改的情况（删除旧参数）
                if (!empty($actionData['id']) && $action->type !== $actionData['type']) {
                    $this->deleteActionParamsByType($action->type, $actionData['id']);
                }

                $action->story_point_id = $story_point_id;
                $action->action_order = $action_order;
                $action->type = $actionData['type'];
                $action->save();

                $actionIds[] = $action->id;

                // 保存处理参数
                switch ($actionData['type']) {
                    case "roomOptionGroup":
                        $this->handleRoomOptionGroupAction($actionData, $action);
                        break;
                    default:
                        // 其他类型走通用流程
                        $this->saveActionParam($actionData, $action);
                        break;
                }


                // 创建所需条件关联
                if (!empty($actionData['requiredConditionIndexes'])) {
                    $conditionIds = [];

                    foreach ($actionData['requiredConditionIndexes'] as $index) {
                        if (isset($storyPoint->conditions[$index])) {
                            $conditionIds[] = $storyPoint->conditions[$index]->id;
                        }
                    }

                    if (!empty($conditionIds)) {
                        try {
                            // 使用 Eloquent 关系附加条件
                            $action->requiredConditions()->attach($conditionIds);
                        } catch (\Exception $e) {
                            Log::error("关联条件失败: " . $e->getMessage());
                        }
                    }
                }
            }

            // 删除未提及的选项
            StoryPointAction::where('story_point_id', $story_point_id)
                ->whereNotIn('id', $actionIds)
                ->delete();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    // 保存处理参数
    public function saveActionParam($actionData, $action)
    {
        // 1. 定义 action type 到模型类 和 字段映射
        $typeConfig = [
            'assignVariable'       => [
                'class' => ActionAssignVariable::class,
                'fields' => ['variable_source', 'variable_id', 'assign_value', 'operation']
            ],
            'operateButtonPoint'   => [
                'class' => ActionOperateButtonPoint::class,
                'fields' => ['buttonPoint_id', 'status']
            ],
            'animationPlayMode'    => [
                'class' => ActionAnimationPlayMode::class,
                'fields' => ['buttonPoint_id', 'animation_play_count']
            ],
            'updateWeather'        => [
                'class' => ActionWeatherParame::class,
                'fields' => ['weather_type', 'intensity']
            ],
            'roleDialogue'         => [
                'class' => ActionRoleDialogue::class,
                'fields' => ['character_name', 'character_image', 'expression_image', 'dialogue']
            ],
            'chapter'              => [
                'class' => ActionChapter::class,
                'fields' => ['content', 'background', 'chapter_type', 'duration']
            ],
            'setAttribute'         => [
                'class' => ActionSetAttribute::class,
                'fields' => ['attribute_id', 'value', 'operation']
            ],
        ];

        $type = $action->type;

        if (!isset($typeConfig[$type])) {
            throw new \InvalidArgumentException("不支持的操作类型: {$type}");
        }

        $config = $typeConfig[$type];
        $class = $config['class'];
        $fields = $config['fields'];

        try {
            // 2. 获取或创建模型实例
            $actionId = $actionData['id'] ?? null;
            if ($actionId) {
                $model = $class::where('action_id', $actionId)->find();
            }
            if (!isset($model)) {
                $model = new $class();
            }

            // 3. 设置通用字段
            $model->action_id = $action->id;

            // 4. 批量设置参数字段
            $paramData = $actionData['param'] ?? [];
            foreach ($fields as $field) {
                $model->{$field} = $paramData[$field];
            }

            // 5. 保存
            $model->save();
        } catch (\Exception $e) {
            // 可选：记录日志
            throw new \Exception("Failed to save action param for type {$type}: " . $e->getMessage(), 0, $e);
        }
    }

    // 单独处理roomOptionGroup类型
    private function handleRoomOptionGroupAction($actionData, $action) 
    {
        try{
            $roomOptionGroupController = app()->make(\app\controller\v1\editor\optionGroup\RoomOptionGroupController::class);
            $actionData['param']['id'] = $actionData['param']['id'] ?? null;
            $actionData['param']['action_id'] = $action->id;
            $roomOptionGroupController->save($actionData['param']);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

    private function deleteActionParamsByType($type, $actionId)
    {
        switch ($type) {
            case 'assignVariable':
                ActionAssignVariable::where('action_id', $actionId)->delete();
                break;
            case 'operateButtonPoint':
                ActionOperateButtonPoint::where('action_id', $actionId)->delete();
                break;
            case 'animationPlayMode':
                ActionAnimationPlayMode::where('action_id', $actionId)->delete();
                break;
            case 'updateWeather':
                ActionWeatherParame::where('action_id', $actionId)->delete();
                break;
            case 'roleDialogue':
                ActionRoleDialogue::where('action_id', $actionId)->delete();
                break;
            case 'chapter':
                ActionChapter::where('action_id', $actionId)->delete();
                break;
            case 'setAttribute':
                ActionSetAttribute::where('action_id', $actionId)->delete();
            case 'roomOptionGroup':
                RoomOptionGroup::where('action_id', $actionId)->delete();
                break;
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
            'param' => [],
            'requiredConditionIndexes' => $this->getRequiredConditionIndexes($action->requiredConditions, $conditionIdToIndex)
        ];

        // 定义类型与格式化规则的映射表
        $actionFormatMap = [
            'assignVariable' => [
                'relation' => 'assignVariable',
                'fields' => ['variable_source', 'variable_id', 'assign_value', 'operation']
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
                'fields' => ['attribute_id', 'value', 'operation']
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
        if ($actionData['type'] === 'roleDialogue' && isset($actionData['param']['dialogue'])) {
            $actionData['param']['dialogue_Chinese'] = TranslationKeyword::queryKeyByName($actionData['param']['dialogue']);
        } else if ($actionData['type'] === 'roomOptionGroup') {
            // 创建控制器实例
            $roomOptionGroupController = app()->make(\app\controller\v1\client\optionGroup\RoomOptionGroupController::class);
            $optionGroup = RoomOptionGroup::with([
            'roomOptionGroupOption'])->where('action_id', $action->id)->find();
            $actionData['param'] = $roomOptionGroupController->formatRoomOptionGroup($optionGroup);
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
