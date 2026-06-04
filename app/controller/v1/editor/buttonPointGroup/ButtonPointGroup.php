<?php

namespace app\controller\v1\editor\buttonPointGroup;

use app\BaseController;
use think\facade\Db;
use think\facade\Validate;

class ButtonPointGroup extends BaseController
{
    public function index()
    {
        $params = request()->param();
        $validate = Validate::rule([
            'room_id' => 'require|number',
        ]);

        if (! $validate->check($params)) {
            return error($validate->getError(), 400);
        }

        $groups = \app\model\ButtonPointGroup::with(['buttonPoints'])
            ->where('room_id', (int) $params['room_id'])
            ->order('sort', 'asc')
            ->select();

        return success($groups);
    }

    public function save()
    {
        $params = request()->post();
        $validate = Validate::rule([
            'room_id' => 'require|number',
            'name' => 'require',
        ]);

        if (! $validate->check($params)) {
            return error($validate->getError(), 400);
        }

        Db::startTrans();
        try {
            $group = !empty($params['id'])
                ? \app\model\ButtonPointGroup::find((int) $params['id'])
                : new \app\model\ButtonPointGroup();

            if (! $group) {
                return error('分组不存在', 404);
            }

            $group->room_id = (int) $params['room_id'];
            $group->name = $params['name'];
            $group->hidden = (int) ($params['hidden'] ?? 0);
            $group->locked = (int) ($params['locked'] ?? 0);

            if (isset($params['sort'])) {
                $group->sort = (int) $params['sort'];
            } elseif (empty($group->id)) {
                $group->sort = (int) Db::name('button_point_group')
                    ->where('room_id', (int) $params['room_id'])
                    ->count();
            }

            $group->save();
            Db::commit();

            return success($group, empty($params['id']) ? '新建分组成功' : '分组更新成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return error('分组保存失败：' . $e->getMessage(), 500);
        }
    }

    public function delete()
    {
        $params = request()->param();
        $validate = Validate::rule([
            'id' => 'require|number',
        ]);

        if (! $validate->check($params)) {
            return error($validate->getError(), 400);
        }

        Db::startTrans();
        try {
            $groupId = (int) $params['id'];
            \app\model\ButtonPoint::where('button_point_group_id', $groupId)->update(['button_point_group_id' => null]);
            \app\model\ButtonPointGroup::destroy($groupId);
            Db::commit();

            return success([], '分组已删除');
        } catch (\Throwable $e) {
            Db::rollback();
            return error('分组删除失败：' . $e->getMessage(), 500);
        }
    }
}