<?php

declare(strict_types=1);

namespace app\controller\v1\editor\globalResources;

use think\facade\Db;
use think\facade\Validate;
use app\model\globalConfig\Attribute;

class ProjectAttribute
{
    /**
     * 获取数据列表
     */
    public function index()
    {
        $params = request()->param();
        $validate = Validate::rule([
            'project_id' => 'require'
        ]);
        if (!$validate->check($params)) {
            return error($validate->getError(), 400);
        }
        $AttributeData = Attribute::where('project_id', $params['project_id'])->select();

        return success($AttributeData);
    }

    /**
     * 保存游戏数据（创建或更新）
     */
    public function save()
    {
        $param = request()->post();
        $validate = Validate::rule([
            'project_id' => 'require'
        ]);
        if (!$validate->check($param)) {
            return error($validate->getError(), 400);
        }

        try {
            Db::startTrans();

            if (!empty($param['id'])) {
                // 更新操作
                $result = $this->updateAttribute($param);
            } else {
                // 创建操作
                $result = $this->createAttribute($param);
            }

            Db::commit();

            if ($result) {
                $message = !empty($param['id']) ? '更新成功' : '创建成功';
                return success($result, $message);
            } else {
                return error('操作失败', 500);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return error('操作失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新游戏数据及对应的本地化文本
     */
    private function updateAttribute(array $param)
    {
        $Attribute = Attribute::find($param['id']);
        if (!$Attribute) {
            return error('记录不存在');
        }

        $param['update_time'] = date('Y-m-d H:i:s');
        $AttributeSaveResult = $Attribute->save($param);

        return $AttributeSaveResult;
    }

    /**
     * 创建游戏数据及对应的本地化文本
     */
    private function createAttribute(array $param)
    {
        // 移除id字段，确保自增
        unset($param['id']);

        // 设置创建和更新时间
        $currentTime = date('Y-m-d H:i:s');
        $baseAttributeParam = [
            'project_id' => $param['project_id'],
            'create_time' => $currentTime,
            'update_time' => $currentTime,
        ];
        
        $Attribute = new Attribute;
        $Attribute->data(array_merge($param, $baseAttributeParam ));
        $Attribute->save();

        // 返回创建的游戏数组
        return $Attribute;
    }


}
