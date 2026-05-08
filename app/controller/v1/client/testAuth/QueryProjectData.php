<?php

declare(strict_types=1);

namespace app\controller\v1\client\auth;

use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;
use think\Response;

class QueryProjectData
{
    /**
     * 获取项目相关数据（单个城市和房间）
     *
     * @return \think\Response
     */
    public function GetProject(): Response
    {
        // 取 GET 参数
        $projectId = Request::param('project_id', '');
        $cityId = Request::param('city_id', '');
        $roomId = Request::param('room_id', '');
        $isHome = filter_var(Request::param('isHome', true), FILTER_VALIDATE_BOOLEAN);

        // 验证必需参数
        if ($projectId === '') {
            return error('项目ID不能为空', 400);
        }
        if ($cityId === '') {
            return error('城市ID不能为空', 400);
        }

        // 查询项目信息
        $project = $this->getProjectById($projectId);
        if (!$project) {
            return error('项目不存在或已禁用', 404);
        }

        // 查询城市信息
        $city = $this->getCityById($cityId, $projectId);
        if (!$city) {
            return error('城市不存在', 404);
        }

        if (!$isHome) {

            if ($roomId === '') {
                return error('房间ID不能为空', 400);
            }

            // 查询房间信息
            $room = $this->getRoomById($roomId, $city['id']);
            if (!$room) {
                return error('房间不存在', 404);
            }

            // 丰富房间信息
            $roomData = $this->enrichRoomData($room);
            // 丰富房间信息
            $enrichedRoom = $roomData;
        } else {
            // 查询并添加面板标题信息（如果存在）
            $panelTitle = $this->getPanelTitle($projectId);
            $enrichedRoom = $panelTitle;
        }

        // 组装城市信息
        $cityData = [
            'id' => $city['id'],
            'name' => $city['name'],
            'room_list' => $enrichedRoom,
        ];

        // 组装项目信息
        $projectData = [
            'id' => $project['id'],
            'name' => $project['name'],
            'city_list' => [$cityData],
        ];

        return success([$projectData], '资源获取成功');
    }

    /**
     * 下载项目 JSON 数据（所有城市和房间）
     *
     * @return \think\Response
     */
    public function TestDownloadProjectJson(): Response
    {
        // 取 GET 参数
        $projectId = Request::param('project_id', '');

        // 验证必需参数
        if ($projectId === '') {
            return error('项目ID不能为空', 400);
        }

        // 查询项目信息
        $project = $this->getProjectById($projectId);
        if (!$project) {
            return error('项目不存在或已禁用', 404);
        }

        // 查询该项目下的所有城市
        $cities = $this->getAllCitiesByProjectId($projectId);
        if (empty($cities)) {
            return success([[
                'id' => $project['id'],
                'name' => $project['name'],
                'city_list' => [],
            ]], '项目存在，但没有城市数据');
        }


        $projectData = [
            'id' => $project['id'],
            'name' => $project['name'],
            'city_list' => array_map(function ($city) use ($projectId) {
                $rooms = $this->getAllRoomsByCityId($city['id']);
                foreach ($rooms as &$room) {
                    $room = $this->enrichRoomData($room);
                }
                $panelTitle = $this->getPanelTitle($projectId);
                if ($panelTitle) {
                    $rooms = $this->mergePanelTitleWithRooms($rooms, $panelTitle);
                }
                return [
                    'id' => $city['id'],
                    'name' => $city['name'],
                    'room_list' => $rooms,
                ];
            }, $cities),
        ];

        $projectList = [$projectData];
        // 生成文件名
        $filename = 'project_data_' . date('Ymd_His') . '.json';

        // 输出JSON数据并设置为文件下载
        return response()
            ->data(json_encode(
                $projectList,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )) // 格式化+保留中文
            ->header([
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
                'Pragma' => 'no-cache'
            ]);
    }

    /**
     * 根据项目ID获取项目信息
     *
     * @param string $projectId
     * @return array|null
     */
    private function getProjectById(string $projectId): ?array
    {
        return Db::name('project')
            ->where([
                'id' => $projectId,
                'status' => 1
            ])
            ->field(['id', 'name'])
            ->find();
    }

    /**
     * 根据城市ID和项目ID获取城市信息
     *
     * @param string $cityId
     * @param string $projectId
     * @return array|null
     */
    private function getCityById(string $cityId, string $projectId): ?array
    {
        return Db::name('city')
            ->where([
                'id' => $cityId,
                'project_id' => $projectId
            ])
            ->field(['id', 'name'])
            ->find();
    }

    /**
     * 获取指定项目下的所有城市
     *
     * @param string $projectId
     * @return array
     */
    private function getAllCitiesByProjectId(string $projectId): array
    {
        return Db::name('city')
            ->where('project_id', $projectId)
            ->field(['id', 'name'])
            ->select()
            ->toArray();
    }

    /**
     * 根据房间ID和城市ID获取房间信息
     *
     * @param string $roomId
     * @param string $cityId
     * @return array|null
     */
    private function getRoomById(string $roomId, int $cityId): ?array
    {
        return Db::name('room')
            ->where([
                'id' => $roomId,
                'cityId' => $cityId
            ])
            ->field([
                'id',
                'name',
                'imageId',
                'safeZoneId',
                'width',
                'height',
                'isSave',
                'isDestroy'
            ])
            ->order('sort', 'asc')
            ->find();
    }

    /**
     * 获取指定城市下的所有房间
     *
     * @param string $cityId
     * @return array
     */
    private function getAllRoomsByCityId(int $cityId): array
    {
        return Db::name('room')
            ->where('cityId', $cityId)
            ->field([
                'id',
                'name',
                'imageId',
                'safeZoneId',
                'width',
                'height',
                'isSave',
                'isDestroy'
            ])
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 丰富房间数据，包括图片URL、安全区域、按钮点、提示点等
     *
     * @param array $room
     * @return array
     */
    private function enrichRoomData(array $room): array
    {
        // 添加图片 URL
        $imageId = (int)$room['imageId'];
        $room['imageUrl'] = $this->getImageFile($imageId);

        // 添加安全区域信息
        $safeZoneId = (int)$room['safeZoneId'];
        $room['safeZone'] = $this->getSafeZone($safeZoneId);

        // 转换布尔字段
        $room['isSave'] = (bool)$room['isSave'];
        $room['isDestroy'] = (bool)$room['isDestroy'];

        // 移除不必要的字段
        unset($room['imageId']);
        unset($room['safeZoneId']);

        // 查询并添加按钮点信息
        $buttonPoints = $this->getButtonPoints((int)$room['id']);
        $room['buttonPointList'] = $buttonPoints;

        // 查询并添加提示点信息
        $hintPoints = $this->getHintPoints((int)$room['id']);
        $room['hintPointList'] = $hintPoints;

        return $room;
    }

    /**
     * 获取指定城市下的所有房间
     *
     * @param string $cityId
     * @return array
     */
    // 已在上方定义 getAllRoomsByCityId

    /**
     * 获取图片文件路径
     *
     * @param int $imageId
     * @return string|null
     */
    private function getImageFile(int $imageId): ?string
    {
        if ($imageId === 0) {
            return null;
        }
        $image = Db::table('image')
            ->where('id', $imageId)
            ->field('file')
            ->find();
        return $image ? $image['file'] : null;
    }

    /**
     * 获取安全区域信息
     *
     * @param int $safeZoneId
     * @return array|null
     */
    private function getSafeZone(int $safeZoneId): ?array
    {
        if ($safeZoneId === 0) {
            return null;
        }
        return Db::table('SafeZone')
            ->where('id', $safeZoneId)
            ->withoutField(['projectId', 'name', 'create_time', 'update_time'])
            ->find();
    }

    /**
     * 获取按钮点信息
     *
     * @param int $roomId
     * @return array
     */
    private function getButtonPoints(int $roomId): array
    {
        $buttonPoints = Db::name('button_point')
            ->where('room_id', $roomId)
            ->order('sort', 'asc')
            ->withoutField(['name', 'create_time', 'update_time'])
            ->select()
            ->toArray();

        foreach ($buttonPoints as &$buttonPoint) {
            $imageId = (int)$buttonPoint['image_id'];
            $resourceId = (int)$buttonPoint['resource_id'];

            // 添加按钮点图片 URL
            $buttonPoint['imageUrl'] = $imageId > 0 ? $this->getImageFile($imageId) : null;

            // 移除不必要的字段
            unset($buttonPoint['image_id']);

            // 添加资源 URL
            // if ($resourceId > 0) {
            //     $resourceTableName = $this->determineResourceTableName($buttonPoint);
            //     if ($resourceTableName) {
            //         $resource = Db::table($resourceTableName)
            //             ->where('id', $resourceId)
            //             ->find();
            //         $buttonPoint['resourceUrl'] = $resource ? $resource['file'] : null;
            //     }
            // }

            // 转换布尔字段
            $buttonPoint['wxSafeArea'] = (bool)$buttonPoint['wxSafeArea'];
            $buttonPoint['multiLanguage'] = (bool)$buttonPoint['multiLanguage'];

            // 根据类型获取附加参数
            $buttonPoint['param'] = $this->getButtonPointParam($buttonPoint);
        }

        return $buttonPoints;
    }

    /**
     * 确定资源表名
     *
     * @param array $buttonPoint
     * @return string|null
     */
    private function determineResourceTableName(array $buttonPoint): ?string
    {
        $subResourceType = (int)$buttonPoint['sub_resource_type'];
        $resourceType = (int)$buttonPoint['resource_type'];
        $type = (int)$buttonPoint['type'];

        $resourceTableName = null;

        if ($subResourceType === 0) {
            $resourceTableName = $resourceType === 0 ? 'image' : 'audio';
        } elseif ($subResourceType === 1) {
            $resourceTableName = $resourceType === 0 ? 'video' : '';
        } elseif ($subResourceType === 2) {
            $resourceTableName = $resourceType === 0 ? 'animations' : '';
        }

        return $resourceTableName ?: null;
    }

    /**
     * 获取按钮点附加参数
     *
     * @param array $buttonPoint
     * @return array
     */
    private function getButtonPointParam(array $buttonPoint): array
    {
        $type = (int)$buttonPoint['type'];
        $buttonPointId = (int)$buttonPoint['id'];
        $param = [];

        $typeToTableMap = [
            1 => 'button_point_tip',
            2 => 'button_point_draggable',
            3 => 'button_point_rotate',
            4 => 'button_point_move',
            5 => 'button_point_nineSquarecalligraphyGrid',
            9 => 'button_point_door',
            10 => 'button_point_item',
        ];

        $table = $typeToTableMap[$type] ?? null;

        if ($table) {
            switch ($type) {
                case 2:
                    $dragDropResetRecord = Db::table($table)
                        ->where('button_point_id', $buttonPointId)
                        ->field('DragDropRestrict')
                        ->find();

                    $fieldsToExclude = $dragDropResetRecord && $dragDropResetRecord['DragDropRestrict'] === 0
                        ? ['anchor', 'target_x', 'target_y', 'create_time', 'update_time']
                        : ['target_button_point_id', 'create_time', 'update_time'];

                    $paramData = Db::table($table)
                        ->where('button_point_id', $buttonPointId)
                        ->withoutField($fieldsToExclude)
                        ->find();

                    $paramData['DragDropReset'] = (bool)($paramData['DragDropReset'] ?? false);
                    $paramData['DragDropRestrict'] = (bool)($paramData['DragDropRestrict'] ?? false);
                    $param = $paramData;
                    break;

                case 5:
                    $blankGridRecord = Db::table($table)
                        ->where('button_point_id', $buttonPointId)
                        ->field('blankGrid')
                        ->find();

                    $fieldsToExclude = $blankGridRecord && $blankGridRecord['blankGrid'] === 0 ? ['compoundImage', 'create_time', 'update_time'] : [];

                    $paramData = Db::table($table)
                        ->where('button_point_id', $buttonPointId)
                        ->withoutField($fieldsToExclude)
                        ->find();

                    $paramData['blankGrid'] = (bool)($paramData['blankGrid'] ?? false);
                    $paddingImageId = (int)($paramData['paddingImage'] ?? 0);
                    $paramData['paddingImage'] = $paddingImageId > 0
                        ? Db::table('image')->where('id', $paddingImageId)->field('file')->find()['file']
                        : null;

                    if (($paramData['blankGrid'] ?? false)) {
                        $compoundImageId = (int)($paramData['compoundImage'] ?? 0);
                        $paramData['compoundImage'] = $compoundImageId > 0
                            ? Db::table('image')->where('id', $compoundImageId)->field('file')->find()['file']
                            : null;
                    }

                    $param = $paramData;
                    break;

                case 9:
                    $doorTypeRecord = Db::table($table)
                        ->where('button_point_id', $buttonPointId)
                        ->field('doorType')
                        ->find();

                    $fieldsToExclude = [
                        'id',
                        'button_point_id',
                        'isOpen',
                        'doorCityId',
                        'doorRoomId',
                        'successVoice',
                        'errorVoice',
                        'doorType'
                    ];

                    $doorType = $doorTypeRecord['doorType'] ?? '';
                    switch ($doorType) {
                        case 'BasicDoor':
                            $fieldsToExclude = array_merge($fieldsToExclude, ['itemsID', 'itemCount', 'lockText']);
                            break;
                        case 'NumericCodeDoor':
                            $fieldsToExclude = array_merge($fieldsToExclude, ['password', 'count', 'lockText']);
                            break;
                        case 'AlphaKeyDoor':
                            $fieldsToExclude = array_merge($fieldsToExclude, ['password', 'count', 'lockText']);
                            break;
                        case 'DraggableDoor':
                            $fieldsToExclude = array_merge($fieldsToExclude, ['moveOrientation', 'moveDistance']);
                            break;
                        case 'LogicDoor':
                            $fieldsToExclude = array_merge($fieldsToExclude, ['pointAnchors', 'pointX', 'pointY']);
                            break;
                    }

                    $paramData = Db::table($table)
                        ->where('button_point_id', $buttonPointId)
                        ->field($fieldsToExclude)
                        ->find();

                    $paramData['isOpen'] = (bool)($paramData['isOpen'] ?? false);
                    $successVoiceId = (int)($paramData['successVoice'] ?? 0);
                    $paramData['successVoice'] = $successVoiceId > 0
                        ? Db::table('audio')->where('id', $successVoiceId)->field('file')->find()['file']
                        : null;

                    $errorVoiceId = (int)($paramData['errorVoice'] ?? 0);
                    $paramData['errorVoice'] = $errorVoiceId > 0
                        ? Db::table('audio')->where('id', $errorVoiceId)->field('file')->find()['file']
                        : null;

                    $param = $paramData;
                    break;

                case 10:
                    $itemsTypeRecord = Db::table($table)
                        ->where('button_point_id', $buttonPointId)
                        ->field('itemsType')
                        ->find();

                    $fieldsToExclude = [];
                    $itemsType = $itemsTypeRecord['itemsType'] ?? '';

                    switch ($itemsType) {
                        case 'PickUp':
                            $fieldsToExclude = ['items', 'zoomRatio'];
                            break;
                        case 'Preview':
                            $fieldsToExclude = ['itemsID', 'itemCount'];
                            break;
                    }

                    $paramData = Db::table($table)
                        ->where('button_point_id', $buttonPointId)
                        ->withoutField($fieldsToExclude)
                        ->find();

                    if ($itemsType === 'Preview') {
                        $itemsId = (int)($paramData['items'] ?? 0);
                        $paramData['items'] = $itemsId > 0
                            ? Db::table('image')->where('id', $itemsId)->field('file')->find()['file']
                            : null;
                    }

                    $param = $paramData;
                    break;

                default:
                    $paramData = Db::table($table)
                        ->where('button_point_id', $buttonPointId)
                        ->withoutField(['create_time', 'update_time'])
                        ->find();
                    $param = $paramData ?: [];
                    break;
            }
        }

        return $param;
    }

    /**
     * 获取提示点信息
     *
     * @param int $roomId
     * @return array
     */
    private function getHintPoints(int $roomId): array
    {
        $hintPoints = Db::name('hint_point')
            ->where('room_id', $roomId)
            ->order('sort', 'asc')
            ->withoutField(['name', 'create_time', 'update_time'])
            ->select()
            ->toArray();

        foreach ($hintPoints as &$hintPoint) {
            $helpType = (int)$hintPoint['help_type'];
            $hintPointId = (int)$hintPoint['id'];
            $table = $this->getHintPointTable($helpType);

            if ($table) {
                $param = Db::table($table)
                    ->where('hint_Point_id', $hintPointId)
                    ->withoutField(['create_time', 'update_time'])
                    ->find();

                if ($helpType === 3) { // 假设 3 对应 hint_point_image
                    $imageId = (int)($param['image_id'] ?? 0);
                    if ($imageId > 0) {
                        $param['imageUrl'] = Db::table('image')
                            ->where('id', $imageId)
                            ->field('file')
                            ->find()['file'];
                        unset($param['image_id']);
                    }
                }

                $hintPoint['param'] = $param ?: [];
            } else {
                $hintPoint['param'] = [];
            }
        }

        return $hintPoints;
    }

    /**
     * 获取提示点对应的表名
     *
     * @param int $helpType
     * @return string|null
     */
    private function getHintPointTable(int $helpType): ?string
    {
        $typeToTableMap = [
            1 => 'hint_point_specialEffect',
            2 => 'hint_point_scaleUp',
            3 => 'hint_point_image',
            4 => 'hint_point_number',
            5 => 'hint_point_letters',
        ];

        return $typeToTableMap[$helpType] ?? null;
    }

    /**
     * 获取面板标题信息
     *
     * @param string $projectId
     * @return array|null
     */
    private function getPanelTitle(string $projectId): ?array
    {
        $panelTitle = Db::table('panel_title')
            ->where('project_id', $projectId)
            ->withoutField(['content', 'create_time', 'update_time'])
            ->find();

        if (!$panelTitle) {
            return null;
        }

        // 添加背景图片 URL
        $backgroundId = (int)$panelTitle['background_id'];
        $panelTitle['imageUrl'] = $backgroundId > 0 ? $this->getImageFile($backgroundId) : null;
        unset($panelTitle['background_id']);

        // 查询面板标题项
        $listItems = Db::table('panel_title_item')
            ->where('panel_title_id', $panelTitle['id'])
            ->withoutField(['content', 'panel_title_id', 'create_time', 'update_time'])
            ->select()
            ->toArray();

        foreach ($listItems as &$item) {
            // 转换多语言布尔字段
            $item['multiLanguage'] = (bool)$item['multiLanguage'];

            // 添加背景图片 URL
            $backgroundId = (int)$item['background_id'];
            $item['imageUrl'] = $backgroundId > 0 ? $this->getImageFile($backgroundId) : null;
            unset($item['background_id']);

            // 查询本地化文本
            $localizationText = Db::table('panel_title_localizationText')
                ->where('panel_title_item_id', $item['id'])
                ->withoutField(['panel_title_item_id', 'create_time', 'update_time'])
                ->find();

            if ($localizationText && !empty($localizationText['content'])) {
                $item['localizationText'] = $localizationText;
            }

            // 处理按钮类型
            $buttonType = (int)$item['button_type'];
            if ($buttonType === 1) {
                $param = Db::table('panel_title_start')
                    ->where('panel_title_item_id', $item['id'])
                    ->withoutField(['panel_title_item_id', 'create_time', 'update_time'])
                    ->find();

                if ($param) {
                    $param['doorCityId'] = !empty($param['city_id']) ? (int)$param['city_id'] : 0;
                    unset($param['city_id']);

                    $param['doorRoomId'] = !empty($param['room_id']) ? (int)$param['room_id'] : null;
                    unset($param['room_id']);

                    $param['successVoice'] = !empty($param['success_audio']) ? Db::table('audio')
                        ->where('id', $param['success_audio'])
                        ->field('file')
                        ->find()['file'] : null;
                    unset($param['success_audio']);
                    $param['errorVoice'] = !empty($param['error_audio']) ? Db::table('audio')
                        ->where('id', $param['error_audio'])
                        ->field('file')
                        ->find()['file'] : null;
                    unset($param['error_audio']);
                }

                $item['param'] = $param;
            }
        }

        $panelTitle['buttonPointList'] = $listItems;
        return $panelTitle;
    }

    /**
     * 将面板标题与房间列表合并
     *
     * @param array $rooms
     * @param array $panelTitle
     * @return array
     */
    private function mergePanelTitleWithRooms(array $rooms, array $panelTitle): array
    {
        // 将面板标题插入到房间列表的开头
        array_unshift($rooms, $panelTitle);
        return $rooms;
    }
}
