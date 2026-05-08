<?php

namespace app\controller\v1\editor\optionGroup;

use think\facade\Log; // 引入日志
use think\facade\Db;
use app\BaseController;
use think\facade\Request;
use think\facade\Validate;
use app\model\optionGroup\roomOptionGroup\RoomOptionGroup;
use app\model\optionGroup\roomOptionGroup\RoomOptionGroupOption;
use app\model\optionGroup\roomOptionGroup\RoomOptionGroupOptionAction;
use app\model\optionGroup\roomOptionGroup\RoomOptionGroupOptionActionModifyStoryValue;
use app\model\optionGroup\roomOptionGroup\RoomOptionGroupOptionActionMsg;

class RoomOptionGroupController extends BaseController
{
    /**
     * 获取选项组
     */
    public function index()
    {
        $params = request()->param();
        $validate = Validate::rule([
            'action_id' => 'require'
        ]);
        if (!$validate->check($params)) {
            return error($validate->getError(), 400);
        }

        $roomOptionGroups = RoomOptionGroup::with([
            'roomOptionGroupOption'
        ])->where('action_id', $params['action_id'])->select();

        // 格式化数据
        $formattedData = [];
        foreach ($roomOptionGroups as $optionGroup) {
            $formattedData[] = $this->formatRoomOptionGroup($optionGroup);
        }

        return success($formattedData);
    }

    #region 保存
    public function save($params)
    {
        // $params = Request::post();
        // $validate = Validate::rule(['action_id' => 'require']);
        // if (!$validate->check($params)) {
        //     return error($validate->getError(), 400);
        // }

        Db::startTrans(); // 开启事务
        try {

            $group = empty($params['id'])
                ? new RoomOptionGroup()
                : RoomOptionGroup::find($params['id']);

            $group->action_id = $params['action_id'];
            $group->name = $params['name'];
            $group->save();

            $this->saveOption($params['options'] ?? [], $group->id);

            Db::commit(); // 提交
            return success($group);
        } catch (\Exception $e) {
            Db::rollback(); // 回滚
            throw new \Exception($e->getMessage());
        }
    }

    // 保存选项
    public function saveOption($options, $option_group_id)
    {
        try {
            $existingOptionIds = [];
            foreach ($options as $optionData) {
                $option = empty($optionData['id'])
                    ? new RoomOptionGroupOption()
                    : RoomOptionGroupOption::find($optionData['id']);

                $option->option_group_id = $option_group_id;
                $option->name = $optionData['name'];
                $option->save();

                $existingOptionIds[] = $option->id;
                // 保存处理
                $this->saveAction($optionData['actions'] ?? [], $option->id);
            }

            // 删除未提及的选项
            RoomOptionGroupOption::where('option_group_id', $option_group_id)
                ->whereNotIn('id', $existingOptionIds)
                ->delete();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    // 保存选项
    public function saveAction($actions, $option_id)
    {
        try {
            $existingActionIds = [];
            foreach ($actions as $actionData) {
                $action = empty($actionData['id'])
                    ? new RoomOptionGroupOptionAction()
                    : RoomOptionGroupOptionAction::find($actionData['id']);

                // 处理类型修改的情况
                if (!empty($actionData['id'])) {
                    if ($action->type !== $actionData['type']) {
                        switch ($action->type) {
                            case 'message':
                                RoomOptionGroupOptionActionMsg::where('option_action_id', $actionData['id'])->delete();
                                break;
                            case 'modifyStoryValue':
                                RoomOptionGroupOptionActionModifyStoryValue::where('option_action_id', $actionData['id'])->delete();
                                break;
                        }
                    }
                }
                $action->option_id = $option_id;
                $action->type = $actionData['type'];
                $action->save();
                $existingActionIds[] = $action->id;

                // 更新类型参数
                $this->saveActionParam($action, $actionData);
            }

            // 删除未提及的 action
            RoomOptionGroupOptionAction::where('option_id', $option_id)
                ->whereNotIn('id', $existingActionIds)
                ->delete();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function saveActionParam($action, $actionData)
    {
        $typeConfig = [
            'message' => [
                'class' => RoomOptionGroupOptionActionMsg::class,
                'fields' => ['message']
            ],
            'modifyStoryValue' => [
                'class' => RoomOptionGroupOptionActionModifyStoryValue::class,
                'fields' => ['target', 'key', 'operation', 'value']
            ],
        ];

        $type = $action->type;

        if (!isset($typeConfig[$type])) {
            throw new \InvalidArgumentException("Unsupported action type: {$type}");
        }

        $config = $typeConfig[$type];
        $class = $config['class'];
        $fields = $config['fields'];

        try {
            $model = null;
            $optionActionId = $actionData['param']['option_action_id'] ?? null;

            if (!empty($optionActionId)) {
                $model = $class::where('option_action_id', $optionActionId)->find();
            }

            $model = $model ?? new $class();
            $model->option_action_id = $action->id;

            $paramData = $actionData['param'] ?? [];
            foreach ($fields as $field) {
                if (!array_key_exists($field, $paramData)) {
                    throw new \InvalidArgumentException("Missing required field: {$field} for action type: {$type}");
                }
                $model->{$field} = $paramData[$field];
            }

            $model->save();

        } catch (\Exception $e) {
            throw new \Exception("Failed to save action param for type {$type}: " . $e->getMessage(), 0, $e);
        }
    }


    #endregion

    #region 格式化选项组数据
    /**
     * 格式化选项组数据
     */
    public function formatRoomOptionGroup($optionGroup)
    {
        $formatted = [
            'id' => $optionGroup->id,
            'action_id' => $optionGroup->action_id,
            'name' => $optionGroup->name,
            'options' => []
        ];

        // 格式化选项列表
        foreach ($optionGroup->roomOptionGroupOption as $option) {
            $formatted['options'][] = $this->formatOption($option);
        }

        return $formatted;
    }

    /**
     * 格式化单个选项
     */
    private function formatOption($option)
    {
        $optionData = [
            'id' => $option->id,
            'option_group_id' => $option->option_group_id,
            'name' => $option->name,
            'actions' => []
        ];

        foreach ($option->roomOptionGroupOptionAction as $action) {
            $optionData['actions'][] = $this->formatAction($action);
        }


        return $optionData;
    }

    // 格式化单个选项的处理
    private function formatAction($action)
    {
        // 动作基础信息
        $actionData = [
            'id' => $action->id,
            'option_id' => $action->option_id,
            'type' => $action->type
        ];

        // 定义类型与格式化规则的映射表
        $actionFormatMap = [
            'message' => [
                'relation' => 'roomOptionGroupOptionActionMsg',
                'fields' => [
                    'option_action_id' => 'option_action_id',
                    'message' => 'message'
                ]
            ],
            'modifyStoryValue' => [
                'relation' => 'roomOptionGroupOptionActionModifyStoryValue',
                'fields' => [
                    'option_action_id' => 'option_action_id',
                    'target' => 'target',
                    'key' => 'key',
                    'operation' => 'operation',
                    'value' => 'value',
                ]
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

        return $actionData;
    }

    #endregion


}
