<?php
namespace app\controller\v1\editor\hintPoint;

use think\exception\ValidateException;
use think\facade\Request;
use think\facade\Db;

class HintPoint
{
    /**
     * 类型与表名映射关系
     */
    private $typeTableMap = [
        1 => 'hint_point_specialEffect',
        2 => 'hint_point_scaleUp',
        3 => 'hint_point_image',
        4 => 'hint_point_number',
        5 => 'hint_point_letters'
    ];

    /**
     * 新建保存 HintPoint
     * @return \think\response\Json
     */
    public function newSaveHintPoint()
    {
        // 取参数
        $roomId = Request::post('room_id');
        $id = Request::post('id'); 
        $name = Request::post('name'); 
        $helpType = Request::post('helpType'); 
        $param = Request::post('param');

        // 更严格的参数验证
        if (empty($roomId) || !is_numeric($roomId)) {
            return error('room_id 参数错误', 400);
        }
        if (empty($name)) {
            return error('name 参数错误', 400);
        }
        if (empty($helpType) || !isset($this->typeTableMap[$helpType])) {
            return error('helpType 参数错误', 400);
        }

        // 开启事务
        Db::startTrans();
        try { 
            if (empty($id)) {   
                // 当前 room 里已有多少条记录
                $maxSort = Db::table('hint_point')
                            ->where('room_id', $roomId)
                            ->count();  
                // 插入数据
                $newHintPointId = Db::table('hint_point')
                    ->insertGetId([
                    'room_id' => $roomId,
                    'name' => $name,
                    'sort' => $maxSort,
                    'help_type' => $helpType,
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ]); 

                // 创建对应参数
                $this->createTypeData($newHintPointId, $helpType, $param);
                
            } else {
                // 获取原类型
                $originalType = Db::table('hint_point')
                                ->where('id', $id)
                                ->value('help_type');

                // 更新主表数据
                Db::table('hint_point') 
                  ->where('id', $id)
                  ->update([
                    'room_id' => $roomId,
                    'name' => $name,
                    'help_type' => $helpType,
                    'update_time' => date('Y-m-d H:i:s'),
                ]); 

                // 如果类型发生变化，删除原类型数据并创建新类型数据
                if ($originalType != $helpType) {
                    $this->deleteTypeData($id, $originalType);
                    $this->createTypeData($id, $helpType, $param);
                } else {
                    // 类型未变，直接更新
                    $this->updateTypeData($id, $helpType, $param);
                }
            }   
            
            Db::commit();  
            return success(true, empty($id) ? '新建成功' : '更新成功'); 
        } catch (\Throwable $e) {
            Db::rollback();   
            return error(empty($id) ? '新建失败' : '更新失败' . $e->getMessage(), 500); 
        }
    }

    /**
     * 创建类型对应的数据
     */
    private function createTypeData($hintPointId, $helpType, $param)
    {
        $table = $this->typeTableMap[$helpType];
        $data = [
            'hint_point_id' => $hintPointId,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        // 根据不同类型添加特定字段
        switch ($helpType) {
            case 1:
            case 2:
            case 4:
            case 5:
                $data['button_point_id'] = $param['button_point_id'] ?? '';
                break;
            case 3:
                $data['image_id'] = $param['image_id'] ?? '';
                $data['width'] = $param['width'] ?? 0;
                $data['height'] = $param['height'] ?? 0;
                break;
        }

        Db::table($table)->insert($data);
    }

    /**
     * 更新类型对应的数据
     */
    private function updateTypeData($hintPointId, $helpType, $param)
    {
        $table = $this->typeTableMap[$helpType];
        $data = [
            'update_time' => date('Y-m-d H:i:s'),
        ];

        // 根据不同类型添加特定字段
        switch ($helpType) {
            case 1:
            case 2:
            case 4:
            case 5:
                $data['button_point_id'] = $param['button_point_id'] ?? '';
                Db::table($table)->where('id', $param['id'])->update($data);
                break;
            case 3:
                $data['image_id'] = $param['image_id'] ?? '';
                $data['width'] = $param['width'] ?? 0;
                $data['height'] = $param['height'] ?? 0;
                Db::table($table)->where('id', $param['id'])->update($data);
                break;
        }
    }

    /**
     * 删除类型对应的数据
     */
    private function deleteTypeData($hintPointId, $helpType)
    {
        if (isset($this->typeTableMap[$helpType])) {
            $table = $this->typeTableMap[$helpType];
            Db::table($table)->where('hint_point_id', $hintPointId)->delete();
        }
    }
}
