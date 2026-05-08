<?php

declare(strict_types=1);

namespace app\controller\v1\editor\panel;

use think\facade\Db;
use think\facade\Validate;
use app\model\Panel\attribute\PanelAttribute;

class Attribute
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
        $AttributeData = PanelAttribute::where('project_id', $params['project_id'])->select();

        return success($AttributeData);
    }
    public function delete()
    {
        $params = request()->param();

        $validate = Validate::rule([
            'project_id' => 'require|number',
            'group_id'   => 'require|number',
        ]);

        if (! $validate->check($params)) {
            return error($validate->getError(), 400);
        }

        // 只删除属于该项目的 group 数据（更安全）
        $deletedRows = PanelAttribute::where([
            ['project_id', '=',  $params['project_id']],
            ['group_id', '=',  $params['group_id']],
        ])->delete();

        return success([],  $deletedRows > 0 ? '删除成功' : '未找到匹配的数据');
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
        $Attribute = PanelAttribute::find($param['id']);
        if (!$Attribute) {
            return error('记录不存在');
        }

        $param['image'] = $param['image'] ?? null;
        $param['content'] = $param['content'] ?? null;
        $param['update_time'] = date('Y-m-d H:i:s');

        $AttributeSaveResult = $Attribute->save($param);

        return $AttributeSaveResult;
    }

    /**
     * 创建游戏数据及对应的本地化文本
     */
    private function createAttribute(array $param)
    {
        $groupId = (int) (microtime(true) * 10000);

        $baseAttributeParam = [
            'project_id' =>  $param['project_id'],
            'group_id'   =>  $groupId
        ];
        $baseIcon = array_merge($baseAttributeParam, [
            'type'    => 'icon',
            'image' =>  $param['icon_image'] ?? null,
            'content' => null,
            'x'       => -100,
        ]);

        $baseLabel = array_merge($baseAttributeParam, [
            'type'    => 'label',
            'image' =>  $param['label_image'] ?? null,
            'content' =>  $param['content'] ?? null,
        ]);

        $baseValue = array_merge($baseAttributeParam, [
            'type'    => 'value',
            'image' =>  $param['value_image'] ?? null,
            'content' =>  $param['content'] ?? null,
            'x'       => 100,
        ]);

        $Attribute = new PanelAttribute();
        $Attribute->data($baseIcon);
        $Attribute->save();
        $Attribute = new PanelAttribute();
        $Attribute->data($baseLabel);
        $Attribute->save();
        $Attribute = new PanelAttribute();
        $Attribute->data($baseValue);
        $Attribute->save();


        return  $Attribute;
    }
}
