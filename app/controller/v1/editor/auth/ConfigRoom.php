<?php

declare(strict_types=1);

namespace app\controller\v1\editor\auth;

use think\facade\Request;
use think\facade\Db;

class ConfigRoom
{
    private function buildCloneName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '副本';
        }

        if (preg_match('/-副本$/u', $name)) {
            return $name;
        }

        return $name . '-副本';
    }

    /**
     * 克隆房间
     */
    public function cloneRoom()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $roomId = Request::post('room_id/d');
        if (empty($roomId)) {
            return error('房间ID不能为空', 400);
        }

        $sourceRoom = Db::name('room')->where('id', $roomId)->find();
        if (empty($sourceRoom)) {
            return error('房间不存在', 404);
        }

        $cloneName = trim((string) Request::post('name/s', ''));
        if ($cloneName === '') {
            $cloneName = $this->buildCloneName((string) ($sourceRoom['name'] ?? 'room'));
        }

        Db::startTrans();
        try {
            $newRoomRow = $sourceRoom;
            unset($newRoomRow['id'], $newRoomRow['create_time'], $newRoomRow['update_time']);
            $newRoomRow['name'] = $cloneName;
            $newRoomRow['sort'] = Db::name('room')
                ->where('cityId', (int) $sourceRoom['cityId'])
                ->count();
            $newRoomRow['create_time'] = date('Y-m-d H:i:s');
            $newRoomRow['update_time'] = date('Y-m-d H:i:s');

            $newRoomId = Db::name('room')->insertGetId($newRoomRow);
            if ($newRoomId <= 0) {
                throw new \Exception('房间克隆失败');
            }

            $roomIdMap = [(int) $roomId => (int) $newRoomId];
            $buttonPointMap = [];
            $stats = $this->cloneRoomChildren(
                (int) $roomId,
                (int) $newRoomId,
                $roomIdMap,
                $buttonPointMap,
                (int) $sourceRoom['cityId'],
                (int) $sourceRoom['cityId']
            );

            Db::commit();
            return success([
                'id' => $newRoomId,
                'room_id' => $newRoomId,
                'stats' => $stats,
            ], '房间克隆成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return error('房间克隆失败：' . $e->getMessage(), 500);
        }
    }

    public function cloneRoomChildren(int $sourceRoomId, int $targetRoomId, array &$roomIdMap, array &$buttonPointMap, ?int $sourceCityId = null, ?int $targetCityId = null): array
    {
        $stats = $this->cloneRoomCoreData($sourceRoomId, $targetRoomId, $roomIdMap, $buttonPointMap, $sourceCityId, $targetCityId);
        $dependentStats = $this->cloneRoomDependentData($sourceRoomId, $targetRoomId, $roomIdMap, $buttonPointMap, $sourceCityId, $targetCityId);

        foreach ($dependentStats as $key => $value) {
            if (!isset($stats[$key])) {
                $stats[$key] = 0;
            }
            $stats[$key] += (int) $value;
        }

        return $stats;
    }

    public function cloneRoomCoreData(int $sourceRoomId, int $targetRoomId, array &$roomIdMap, array &$buttonPointMap, ?int $sourceCityId = null, ?int $targetCityId = null): array
    {
        $stats = [
            'button_point_group' => 0,
            'button_point' => 0,
            'button_point_param' => 0,
            'button_point_localizationText' => 0,
            'hint_point' => 0,
            'hint_point_condition' => 0,
            'hint_point_param' => 0,
            'story_point' => 0,
            'story_point_condition' => 0,
            'story_point_condition_param' => 0,
            'story_point_action' => 0,
            'story_point_action_param' => 0,
            'story_point_action_required_condition' => 0,
            'room_story_variables' => 0,
            'room_option_group' => 0,
            'room_option_group_option' => 0,
            'room_option_group_option_action' => 0,
            'room_option_group_option_action_param' => 0,
        ];

        $groupMap = [];
        $groupList = Db::name('button_point_group')
            ->where('room_id', $sourceRoomId)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        foreach ($groupList as $group) {
            $newGroup = $group;
            unset($newGroup['id'], $newGroup['create_time'], $newGroup['update_time']);
            $newGroup['room_id'] = $targetRoomId;
            $newGroup['create_time'] = date('Y-m-d H:i:s');
            $newGroup['update_time'] = date('Y-m-d H:i:s');

            $newGroupId = Db::name('button_point_group')->insertGetId($newGroup);
            if ($newGroupId <= 0) {
                throw new \Exception('按钮分组克隆失败');
            }

            $groupMap[(int) $group['id']] = (int) $newGroupId;
            $stats['button_point_group']++;
        }

        $buttonPointList = Db::name('button_point')
            ->where('room_id', $sourceRoomId)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        foreach ($buttonPointList as $buttonPoint) {
            $newButtonPoint = $buttonPoint;
            unset($newButtonPoint['id'], $newButtonPoint['create_time'], $newButtonPoint['update_time']);
            $newButtonPoint['room_id'] = $targetRoomId;
            $groupId = (int) ($buttonPoint['button_point_group_id'] ?? 0);
            $newButtonPoint['button_point_group_id'] = $groupId && isset($groupMap[$groupId]) ? $groupMap[$groupId] : null;
            $newButtonPoint['create_time'] = date('Y-m-d H:i:s');
            $newButtonPoint['update_time'] = date('Y-m-d H:i:s');

            $newButtonPointId = Db::name('button_point')->insertGetId($newButtonPoint);
            if ($newButtonPointId <= 0) {
                throw new \Exception('按钮点克隆失败');
            }

            $buttonPointMap[(int) $buttonPoint['id']] = (int) $newButtonPointId;
            $stats['button_point']++;
            $stats['button_point_param'] += $this->cloneButtonPointParam(
                (int) $buttonPoint['id'],
                (int) $newButtonPointId,
                $buttonPointMap,
                $sourceCityId,
                $targetCityId,
                $roomIdMap
            );
            $stats['button_point_localizationText'] += $this->cloneButtonPointLocalizationText((int) $buttonPoint['id'], (int) $newButtonPointId);
        }

        return $stats;
    }

    public function cloneRoomDependentData(int $sourceRoomId, int $targetRoomId, array &$roomIdMap, array &$buttonPointMap, ?int $sourceCityId = null, ?int $targetCityId = null): array
    {
        $stats = [
            'hint_point' => 0,
            'hint_point_condition' => 0,
            'hint_point_param' => 0,
            'story_point' => 0,
            'story_point_condition' => 0,
            'story_point_condition_param' => 0,
            'story_point_action' => 0,
            'story_point_action_param' => 0,
            'story_point_action_required_condition' => 0,
            'room_story_variables' => 0,
            'room_option_group' => 0,
            'room_option_group_option' => 0,
            'room_option_group_option_action' => 0,
            'room_option_group_option_action_param' => 0,
        ];

        $hintPointList = Db::name('hint_point')
            ->where('room_id', $sourceRoomId)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        foreach ($hintPointList as $hintPoint) {
            $newHintPoint = $hintPoint;
            unset($newHintPoint['id'], $newHintPoint['create_time'], $newHintPoint['update_time']);
            $newHintPoint['room_id'] = $targetRoomId;
            $newHintPoint['create_time'] = date('Y-m-d H:i:s');
            $newHintPoint['update_time'] = date('Y-m-d H:i:s');

            $newHintPointId = Db::name('hint_point')->insertGetId($newHintPoint);
            if ($newHintPointId <= 0) {
                throw new \Exception('提示点克隆失败');
            }

            $stats['hint_point']++;
            $stats['hint_point_condition'] += $this->cloneHintPointConditions((int) $hintPoint['id'], (int) $newHintPointId);
            $stats['hint_point_param'] += $this->cloneHintPointParam((int) $hintPoint['id'], (int) $newHintPointId, $buttonPointMap);
        }

        $storyPointList = Db::name('story_points')
            ->where('room_id', $sourceRoomId)
            ->order('id', 'asc')
            ->select()
            ->toArray();

        foreach ($storyPointList as $storyPoint) {
            $newStoryPoint = $storyPoint;
            unset($newStoryPoint['id'], $newStoryPoint['create_time'], $newStoryPoint['update_time']);
            $newStoryPoint['room_id'] = $targetRoomId;
            $newStoryPoint['create_time'] = date('Y-m-d H:i:s');
            $newStoryPoint['update_time'] = date('Y-m-d H:i:s');

            $newStoryPointId = Db::name('story_points')->insertGetId($newStoryPoint);
            if ($newStoryPointId <= 0) {
                throw new \Exception('剧情点克隆失败');
            }

            $stats['story_point']++;
            $conditionMap = $this->cloneStoryPointConditions((int) $storyPoint['id'], (int) $newStoryPointId);
            $actionResult = $this->cloneStoryPointActions(
                (int) $storyPoint['id'],
                (int) $newStoryPointId,
                $conditionMap,
                $buttonPointMap,
                $roomIdMap,
                $sourceCityId,
                $targetCityId
            );

            foreach ($actionResult['stats'] as $key => $value) {
                if (!isset($stats[$key])) {
                    $stats[$key] = 0;
                }
                $stats[$key] += (int) $value;
            }
        }

        $roomStoryVariables = Db::name('room_story_variables')
            ->where('room_id', $sourceRoomId)
            ->select()
            ->toArray();

        foreach ($roomStoryVariables as $variable) {
            $newVariable = $variable;
            unset($newVariable['id'], $newVariable['create_time'], $newVariable['update_time']);
            $newVariable['room_id'] = $targetRoomId;
            $newVariable['create_time'] = date('Y-m-d H:i:s');
            $newVariable['update_time'] = date('Y-m-d H:i:s');
            Db::name('room_story_variables')->insertGetId($newVariable);
            $stats['room_story_variables']++;
        }

        return $stats;
    }

    private function cloneButtonPointParam(int $oldButtonPointId, int $newButtonPointId, array $buttonPointMap, ?int $sourceCityId, ?int $targetCityId, array $roomIdMap): int
    {
        $buttonPoint = Db::name('button_point')->where('id', $oldButtonPointId)->find();
        if (empty($buttonPoint)) {
            return 0;
        }

        $table = null;
        switch ((int) $buttonPoint['type']) {
            case 1:
                $table = 'button_point_tip';
                break;
            case 2:
                $table = 'button_point_draggable';
                break;
            case 3:
                $table = 'button_point_rotate';
                break;
            case 4:
                $table = 'button_point_move';
                break;
            case 5:
                $table = 'button_point_nineSquarecalligraphyGrid';
                break;
            case 7:
                $table = 'button_point_set';
                break;
            case 9:
                $table = 'button_point_door';
                break;
            case 10:
                $table = 'button_point_item';
                break;
            case 11:
                $table = 'button_point_chapter';
                break;
        }

        if (empty($table)) {
            return 0;
        }

        $param = Db::table($table)->where('button_point_id', $oldButtonPointId)->find();
        if (empty($param)) {
            return 0;
        }

        $newParam = $param;
        unset($newParam['id'], $newParam['create_time'], $newParam['update_time']);
        $newParam['button_point_id'] = $newButtonPointId;

        if ($table === 'button_point_door') {
            if ($sourceCityId !== null && $targetCityId !== null && isset($newParam['doorCityId']) && (int) $newParam['doorCityId'] === (int) $sourceCityId) {
                $newParam['doorCityId'] = $targetCityId;
            }

            if (isset($newParam['doorRoomId']) && isset($roomIdMap[(int) $newParam['doorRoomId']])) {
                $newParam['doorRoomId'] = $roomIdMap[(int) $newParam['doorRoomId']];
            }
        }

        Db::table($table)->insertGetId($newParam);
        return 1;
    }

    private function cloneButtonPointLocalizationText(int $oldButtonPointId, int $newButtonPointId): int
    {
        $row = Db::table('button_point_localizationText')->where('button_point_id', $oldButtonPointId)->find();
        if (empty($row)) {
            return 0;
        }

        $newRow = $row;
        unset($newRow['id'], $newRow['create_time'], $newRow['update_time']);
        $newRow['button_point_id'] = $newButtonPointId;
        Db::table('button_point_localizationText')->insertGetId($newRow);
        return 1;
    }

    private function cloneHintPointConditions(int $oldHintPointId, int $newHintPointId): int
    {
        $list = Db::table('hint_point_condition')->where('hint_Point_id', $oldHintPointId)->select()->toArray();
        $count = 0;
        foreach ($list as $row) {
            $newRow = $row;
            unset($newRow['id'], $newRow['create_time'], $newRow['update_time']);
            $newRow['hint_Point_id'] = $newHintPointId;
            Db::table('hint_point_condition')->insertGetId($newRow);
            $count++;
        }

        return $count;
    }

    private function cloneHintPointParam(int $oldHintPointId, int $newHintPointId, array $buttonPointMap): int
    {
        $hintPoint = Db::table('hint_point')->where('id', $oldHintPointId)->find();
        if (empty($hintPoint)) {
            return 0;
        }

        $tableMap = [
            1 => 'hint_point_specialEffect',
            2 => 'hint_point_scaleUp',
            3 => 'hint_point_image',
            4 => 'hint_point_number',
            5 => 'hint_point_letters',
        ];

        $table = $tableMap[(int) $hintPoint['help_type']] ?? null;
        if (empty($table)) {
            return 0;
        }

        $row = Db::table($table)->where('hint_Point_id', $oldHintPointId)->find();
        if (empty($row)) {
            return 0;
        }

        $newRow = $row;
        unset($newRow['id'], $newRow['create_time'], $newRow['update_time']);
        $newRow['hint_Point_id'] = $newHintPointId;

        if (isset($newRow['button_point_id']) && isset($buttonPointMap[(int) $newRow['button_point_id']])) {
            $newRow['button_point_id'] = $buttonPointMap[(int) $newRow['button_point_id']];
        }

        Db::table($table)->insertGetId($newRow);
        return 1;
    }

    private function cloneStoryPointConditions(int $oldStoryPointId, int $newStoryPointId): array
    {
        $conditionList = Db::name('story_point_conditions')
            ->where('story_point_id', $oldStoryPointId)
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $conditionMap = [];
        foreach ($conditionList as $condition) {
            $newCondition = $condition;
            unset($newCondition['id'], $newCondition['create_time'], $newCondition['update_time']);
            $newCondition['story_point_id'] = $newStoryPointId;
            $newCondition['create_time'] = date('Y-m-d H:i:s');
            $newCondition['update_time'] = date('Y-m-d H:i:s');

            $newConditionId = Db::name('story_point_conditions')->insertGetId($newCondition);
            if ($newConditionId <= 0) {
                throw new \Exception('剧情条件克隆失败');
            }

            $conditionMap[(int) $condition['id']] = (int) $newConditionId;

            $paramTable = null;
            switch ((string) $condition['type']) {
                case 'storyVariable':
                    $paramTable = 'condition_story_variable';
                    break;
                case 'attribute':
                    $paramTable = 'condition_attribute';
                    break;
            }

            if (!empty($paramTable)) {
                $paramRow = Db::table($paramTable)->where('condition_id', $condition['id'])->find();
                if (!empty($paramRow)) {
                    $newParamRow = $paramRow;
                    unset($newParamRow['id'], $newParamRow['create_time'], $newParamRow['update_time']);
                    $newParamRow['condition_id'] = $newConditionId;
                    Db::table($paramTable)->insertGetId($newParamRow);
                }
            }
        }

        return $conditionMap;
    }

    private function cloneStoryPointActions(int $oldStoryPointId, int $newStoryPointId, array $conditionMap, array $buttonPointMap, array $roomIdMap, ?int $sourceCityId, ?int $targetCityId): array
    {
        $actionList = Db::name('story_point_actions')
            ->where('story_point_id', $oldStoryPointId)
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $actionMap = [];
        $stats = [
            'story_point_action' => 0,
            'story_point_action_param' => 0,
            'story_point_action_required_condition' => 0,
            'room_option_group' => 0,
            'room_option_group_option' => 0,
            'room_option_group_option_action' => 0,
            'room_option_group_option_action_param' => 0,
        ];

        foreach ($actionList as $action) {
            $newAction = $action;
            unset($newAction['id'], $newAction['create_time'], $newAction['update_time']);
            $newAction['story_point_id'] = $newStoryPointId;
            $newAction['create_time'] = date('Y-m-d H:i:s');
            $newAction['update_time'] = date('Y-m-d H:i:s');

            $newActionId = Db::name('story_point_actions')->insertGetId($newAction);
            if ($newActionId <= 0) {
                throw new \Exception('剧情动作克隆失败');
            }

            $actionMap[(int) $action['id']] = (int) $newActionId;
            $stats['story_point_action']++;

            if ((string) $action['type'] === 'roomOptionGroup') {
                $groupStats = $this->cloneRoomOptionGroupTree((int) $action['id'], (int) $newActionId);
                foreach ($groupStats as $key => $value) {
                    if (!isset($stats[$key])) {
                        $stats[$key] = 0;
                    }
                    $stats[$key] += (int) $value;
                }
            } else {
                $stats['story_point_action_param'] += $this->cloneStoryPointActionParam(
                    (int) $action['id'],
                    (int) $newActionId,
                    (string) $action['type'],
                    $buttonPointMap
                );
            }

            $pivotRows = Db::name('action_required_conditions')
                ->where('action_id', $action['id'])
                ->select()
                ->toArray();

            foreach ($pivotRows as $pivotRow) {
                $oldConditionId = (int) $pivotRow['condition_id'];
                if (!isset($conditionMap[$oldConditionId])) {
                    continue;
                }

                $newPivot = $pivotRow;
                unset($newPivot['id'], $newPivot['create_time'], $newPivot['update_time']);
                $newPivot['action_id'] = $newActionId;
                $newPivot['condition_id'] = $conditionMap[$oldConditionId];
                Db::name('action_required_conditions')->insertGetId($newPivot);
                $stats['story_point_action_required_condition']++;
            }
        }

        return [
            'actionMap' => $actionMap,
            'conditionMap' => $conditionMap,
            'stats' => $stats,
        ];
    }

    private function cloneStoryPointActionParam(int $oldActionId, int $newActionId, string $type, array $buttonPointMap): int
    {
        $tableMap = [
            'assignVariable' => 'action_assign_variables',
            'operateButtonPoint' => 'action_operate_button_points',
            'animationPlayMode' => 'action_animation_play_mode',
            'updateWeather' => 'action_weather_parame',
            'roleDialogue' => 'action_role_dialogue',
            'chapter' => 'action_chapter',
            'setAttribute' => 'action_set_attribute',
        ];

        $table = $tableMap[$type] ?? null;
        if (empty($table)) {
            return 0;
        }

        $row = Db::table($table)->where('action_id', $oldActionId)->find();
        if (empty($row)) {
            return 0;
        }

        $newRow = $row;
        unset($newRow['id'], $newRow['create_time'], $newRow['update_time']);
        $newRow['action_id'] = $newActionId;

        if (isset($newRow['buttonPoint_id']) && isset($buttonPointMap[(int) $newRow['buttonPoint_id']])) {
            $newRow['buttonPoint_id'] = $buttonPointMap[(int) $newRow['buttonPoint_id']];
        }

        if (isset($newRow['button_point_id']) && isset($buttonPointMap[(int) $newRow['button_point_id']])) {
            $newRow['button_point_id'] = $buttonPointMap[(int) $newRow['button_point_id']];
        }

        Db::table($table)->insertGetId($newRow);
        return 1;
    }

    private function cloneRoomOptionGroupTree(int $oldActionId, int $newActionId): array
    {
        $stats = [
            'room_option_group' => 0,
            'room_option_group_option' => 0,
            'room_option_group_option_action' => 0,
            'room_option_group_option_action_param' => 0,
        ];

        $groupList = Db::name('room_option_group')
            ->where('action_id', $oldActionId)
            ->order('id', 'asc')
            ->select()
            ->toArray();

        foreach ($groupList as $group) {
            $newGroup = $group;
            unset($newGroup['id'], $newGroup['create_time'], $newGroup['update_time']);
            $newGroup['action_id'] = $newActionId;
            $newGroup['create_time'] = date('Y-m-d H:i:s');
            $newGroup['update_time'] = date('Y-m-d H:i:s');

            $newGroupId = Db::name('room_option_group')->insertGetId($newGroup);
            if ($newGroupId <= 0) {
                throw new \Exception('剧情选项组克隆失败');
            }

            $stats['room_option_group']++;

            $optionList = Db::name('room_option_group_option')
                ->where('option_group_id', (int) $group['id'])
                ->order('id', 'asc')
                ->select()
                ->toArray();

            foreach ($optionList as $option) {
                $newOption = $option;
                unset($newOption['id'], $newOption['create_time'], $newOption['update_time']);
                $newOption['option_group_id'] = $newGroupId;
                $newOption['create_time'] = date('Y-m-d H:i:s');
                $newOption['update_time'] = date('Y-m-d H:i:s');

                $newOptionId = Db::name('room_option_group_option')->insertGetId($newOption);
                if ($newOptionId <= 0) {
                    throw new \Exception('剧情选项克隆失败');
                }

                $stats['room_option_group_option']++;

                $actionList = Db::name('room_option_group_option_action')
                    ->where('option_id', (int) $option['id'])
                    ->order('id', 'asc')
                    ->select()
                    ->toArray();

                foreach ($actionList as $action) {
                    $newAction = $action;
                    unset($newAction['id'], $newAction['create_time'], $newAction['update_time']);
                    $newAction['option_id'] = $newOptionId;
                    $newAction['create_time'] = date('Y-m-d H:i:s');
                    $newAction['update_time'] = date('Y-m-d H:i:s');

                    $newActionId = Db::name('room_option_group_option_action')->insertGetId($newAction);
                    if ($newActionId <= 0) {
                        throw new \Exception('剧情选项动作克隆失败');
                    }

                    $stats['room_option_group_option_action']++;

                    $paramTable = null;
                    switch ((string) $action['type']) {
                        case 'message':
                            $paramTable = 'room_option_group_option_action_msg';
                            break;
                        case 'modifyStoryValue':
                            $paramTable = 'room_option_group_option_action_modifyStoryValue';
                            break;
                    }

                    if (!empty($paramTable)) {
                        $paramRow = Db::table($paramTable)->where('option_action_id', (int) $action['id'])->find();
                        if (!empty($paramRow)) {
                            $newParamRow = $paramRow;
                            unset($newParamRow['id'], $newParamRow['create_time'], $newParamRow['update_time']);
                            $newParamRow['option_action_id'] = $newActionId;
                            Db::table($paramTable)->insertGetId($newParamRow);
                            $stats['room_option_group_option_action_param']++;
                        }
                    }
                }
            }
        }

        return $stats;
    }
    
    /**
     * 获取 ButtonPoint 详情
     */
    public function room_detail()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }
        $room_id = request()->param('room_id');

        $room_data = Db::name('room')
            ->where('id', $room_id)
            ->field([
                'id',
                'cityId',
                'name',
                'imageId',
                'safeZoneId',
                'width',
                'height',
                'room_type',
                'isSave',
                'isDestroy'
            ])
            ->order('sort', 'asc')
            ->find();
        
        $button_point_list = Db::name('button_point')
            ->where('room_id', $room_id)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
        $button_point_group_list = Db::name('button_point_group')
            ->where('room_id', $room_id)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
        foreach ($button_point_group_list as &$group) {
            $group['hidden'] = (int) ($group['hidden'] ?? 0);
            $group['frozen'] = (int) ($group['frozen'] ?? 0);
        }
        foreach ($button_point_list as &$buttonPoint) {
            $buttonPoint['visible'] = (int) ($buttonPoint['visible']);
            $buttonPoint['frozen'] = (int) ($buttonPoint['frozen'] ?? 0);

            if ($buttonPoint['sub_resource_type'] === 2) {
                $animationActionId = $buttonPoint['animation_action'];

                $frameRecord = Db::table('animation_frames')
                    ->where('animation_action_id', $animationActionId)
                    ->field('frameImage')
                    ->order('sort', 'asc')
                    ->find();

                $buttonPoint['animation_action_path'] =
                    ($frameRecord && isset($frameRecord['frameImage']))
                    ? $frameRecord['frameImage']
                    : '';
                // $buttonPoint['hidden'] = false;
            }

            $table = null;
            switch ($buttonPoint['type']) {
                case 1:
                    $table = 'button_point_tip';
                    break;
                case 2:
                    $table = 'button_point_draggable';
                    break;
                case 3:
                    $table = 'button_point_rotate';
                    break;
                case 4:
                    $table = 'button_point_move';
                    break;
                case 5:
                    $table = 'button_point_nineSquarecalligraphyGrid';
                    break;
                case 7:
                    $table = 'button_point_set';
                    break;
                case 9:
                    $table = 'button_point_door';
                    break;
                case 10:
                    $table = 'button_point_item';
                    break;
            }
            
            if ($table) {
                $param = Db::table($table)
                    ->where('button_point_id', $buttonPoint['id'])
                    ->find();
                $buttonPoint['param'] = $param;
            }
            
        }

        $room_data['button_point_list'] = $button_point_list;
        $room_data['button_point_group_list'] = $button_point_group_list;
        return success($room_data, '房间详情');
    }

    /**
     * 新建或更新房间
     */
    public function saveRoom()
    {
        // 获取当前登录用户信息
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        // 获取参数
        $id = Request::post('id/d', 0); // 转为整数，默认0表示新增
        $cityId = Request::post('cityId/d');
        $name = Request::post('name/s');
        $imageId = Request::post('imageId/d');
        $safeZoneId = Request::post('safeZoneId/d');
        $width = Request::post('width/d');
        $height = Request::post('height/d');
        $room_type = Request::post('room_type');
        $isSave = Request::post('isSave/d');
        $isDestroy = Request::post('isDestroy/d');

        // 验证请求参数
        if (empty($cityId)) {
            return error('城市ID不能为空', 400);
        }

        $city = Db::name('city')->find($cityId);
        if (empty($city)) {
            return error('城市不存在', 403);
        }

        if (empty($name)) {
            return error('房间名称不能为空', 400);
        }


        // 验证权限
        if ($id > 0) {
            $room = Db::name('room')->find($id);
            if (empty($room)) {
                return error('无权操作该房间', 403);
            }
        }

        $row = [
            'cityId' => $cityId,
            'name' => $name,
            'width' => $width,
            'height' => $height,
            'imageId' => $imageId,
            'background_audio' => Request::post('background_audio/d', 0),
            'safeZoneId' => $safeZoneId,
            'room_type' => $room_type,
            'isSave' => $isSave,
            'isDestroy' => $isDestroy,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];


        try {
            Db::startTrans();

            if ($id > 0) {
                // 更新房间
                $result = Db::name('room')
                    ->where('id', $id)
                    ->update($row);

                if ($result === false) {
                    throw new \Exception('房间更新失败');
                }
                $projectId = $id;
            } else {
                // 当前 room 里已有多少条记录
                $maxSort = Db::table('room')
                    ->where('cityId', $cityId)
                    ->count();
                // 新增房间
                $row['sort'] = $maxSort;
                $row['create_time'] = date('Y-m-d H:i:s');
                $projectId = Db::name('room')->insertGetId($row);

                if ($projectId <= 0) {
                    throw new \Exception('房间创建失败');
                }
            }

            Db::commit();
            return success(true, $id > 0 ? '房间更新成功' : '房间创建成功');
        } catch (\Exception $e) {
            Db::rollback();
            return error($e->getMessage(), 500);
        }
    }

}
