<?php

declare(strict_types=1);

namespace app\controller\v1\editor\globalResources;

use think\facade\Request;
use think\facade\Db;
use think\facade\Filesystem;

class AnimationResources
{
    /**
     * 上传/更新动画（animations 表）
     * POST /api/v1/GlobalResources/UploadAnimation
     *
     * 表单字段：
     *   id          int     可选，传 0 或不传=新增；传 id=更新
     *   name        string  可选，动画名称
     *   description string  可选，动画描述
     *   project_id   string  必选，项目ID
     */
    public function UploadAnimation()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $id             = (int) Request::post('id', 0);
        $name           = Request::post('name', '新动画');
        $project_id     = (int) Request::post('project_id', 0);
        $width          = (int) Request::post('width', 640);
        $height         = (int) Request::post('height', 640);
        $duration  = (int) Request::post('duration', 100);    // 每帧持续时间
        $loop           = (int) Request::post('loop', 0);               // 或者用每秒帧数
        $isPlaying      = (int) Request::post('isPlaying', 0);          // 是否循环播放
        $currentFrame   = (int) Request::post('currentFrame', 0);       // 当前是否正在播放

        if (empty($project_id)) {
            return error('项目ID不能为空.', 400);
        }

        $row = [
            'name'        => $name,
            'project_id'  => $project_id,
            'width'  => $width,
            'height'  => $height,
            'duration'  => $duration,
            'loop'  => $loop,
            'isPlaying'  => $isPlaying,
            'currentFrame' => $currentFrame,
            'update_time' => date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            // 判断动画是否存在，且属于该项目
            $exists = Db::name('animations')
                ->where(['id' => $id, 'project_id' => $project_id])
                ->find();
            if (!$exists) {
                return error('动画不存在或无权限', 404);
            }
            Db::name('animations')->where('id', $id)->update($row);
            $row['id'] = $id;
        } else {
            $row['create_time'] = date('Y-m-d H:i:s');
            $row['id'] = Db::name('animations')->insertGetId($row);
        }

        return success($row, $id > 0 ? '更新成功' : '添加成功');
    }

    /**
     * 获取某个项目的所有动画
     * GET /api/v1/GlobalResources/GetAnimations
     *
     * 参数：
     *   project_id   string  必选
     */
    public function GetAnimations()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $project_id = Request::param('project_id', '');
        if (empty($project_id)) {
            return error('项目ID不能为空', 400);
        }

        $list = Db::name('animations')
            ->where('project_id', $project_id)
            // ->order('id', 'desc')
            ->select()
            ->toArray();

        return success($list, '获取成功');
    }

    
    /**
     * 获取某个项目的所有动画
     * GET /api/v1/GlobalResources/GetAnimations
     *
     * 参数：
     *   project_id   string  必选
     */
    public function GetAnimationList()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $project_id = Request::param('project_id', '');
        if (empty($project_id)) {
            return error('项目ID不能为空', 400);
        }

        $animationList = Db::name('animations')
            ->where('project_id', $project_id)
            ->field(['id', 'name'])
            ->select()
            ->toArray();
            
        foreach ($animationList as &$animationItem) {
            $animationActionsList = Db::name('animation_actions')
                ->where('animation_id', $animationItem['id'])
                ->field(['id', 'name'])
                ->select()
                ->toArray();
                
            $animationItem['animationFramesList'] = $animationActionsList;
        }
        unset($animationItem);

        return success($animationList, '获取成功');
    }


    /**
     * 删除动画（专用于 animation 表）
     * Delete /api/v1/editor/deleteAnimation
     * 
     * 表单参数：
     *   id int 必须，动画ID
     */
    public function DeleteAnimation()
    {
        $id = (int) Request::get('id'); // ✅ 从 GET 参数获取，因为是 URL query

        if (empty($id) || $id <= 0) {
            return error('动画ID必须为有效正整数', 400);
        }

        $table = 'animations';

        $animation = Db::name($table)->find($id);
        if (!$animation) {
            return error('动画不存在', 404);
        }

        $result = Db::name($table)->where('id', $id)->delete();

        if ($result) {
            return success(null, '动画删除成功');
        } else {
            return error('动画删除失败', 500);
        }
    }


    /**
     * 上传/更新单帧（animation_frames 表）
     * POST /api/v1/GlobalResources/UploadAnimationFrame
     *
     * 表单字段：
     *   id              int     可选，传 0 或不传=新增；传 id=更新
     *   animation_id    int     必选，动画ID
     *   frame_number    int     必选，帧号，如 1, 2, 3...
     *   image           file    必选，帧图片文件
     *   duration        float   可选，帧持续时间（秒）
     *   effects         string  可选，JSON 字符串，如 {"rotate": 90}
     *   project_id       string  可选校验用（可不传）
     */
    public function UploadAnimationFrame()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $id             = (int) Request::post('id', 0);
        $animation_action_id   = (int) Request::post('animation_action_id', 0);
        $name   = Request::post('name', null);
        $duration       = (int) Request::post('duration', 150); // 每帧的显示时长（毫秒）

        $row = [
            'duration'      => $duration,
            'update_time'   => date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            // 更新已有帧
            $exists = Db::name('animation_frames')
                ->where('id', $id)->find();
            if (!$exists) {
                return error('动画帧不存在或无权限', 404);
            }

            Db::name('animation_frames')->where('id', $id)->update($row);
            $row['id'] = $id;
        } else {
            // 新增帧
            if (empty($animation_action_id)) {
                return error('动画动作ID不能为空', 400);
            }

            $file = Request::file('frameImage');
            if (!$file || !$file->isValid()) {
                return error('未上传帧图片或上传失败', 400);
            }

            // 保存帧图片
            $saveDir = "resource/animation";
            $saveName = Filesystem::disk('public')->putFile($saveDir, $file);
            $frameImage = 'storage/' . ltrim($saveName, '/');

            // 验证动画是否存在
            $exists = Db::name('animation_actions')
                ->where('id', $animation_action_id)->find();
            if (!$exists) {
                return error('动画不存在或无权限', 404);
            }

            // 计算当前最大排序
            $maxSort = Db::table('animation_frames')
                ->where('animation_action_id', $animation_action_id)
                ->count();

            $row['animation_action_id'] = $animation_action_id;
            $row['name']       = $name;
            $row['frameImage'] = $frameImage;
            $row['sort']       = $maxSort;
            $row['create_time'] = date('Y-m-d H:i:s');

            // ✅ 新增帧到数据库
            $row['id'] = Db::name('animation_frames')->insertGetId($row);
        }

        return success($row, $id > 0 ? '更新成功' : '添加成功');
    }

    /**
     * 获取某个动画的所有帧
     * GET /api/v1/GlobalResources/GetAnimationFrames
     *
     * 参数：
     *   animation_id  int  必选
     */
    public function GetAnimationFrames()
    {
        $animation_id = (int) Request::param('animation_id', 0);
        if ($animation_id <= 0) {
            return error('动画ID不能为空', 400);
        }

        $list = Db::name('animation_frames')
            ->where('animation_id', $animation_id)
            ->order('sort', 'asc')
            ->select()
            ->toArray(); 

        return success($list, '获取成功');
    }

    /**
     * 删除动画帧（专用于 animation_frames 表）
     * POST /api/v1/editor/deleteAnimationFrame
     * 
     * 表单参数：
     *   id int 必须，动画帧ID
     */
    public function DeleteAnimationFrame()
    {
        $id = (int) Request::get('id');

        if (empty($id)) {
            return error('动画帧ID必须为有效正整数', 400);
        }

        $table = 'animation_frames';
        $frame = Db::name($table)->find($id);

        if (!$frame) {
            return error('动画帧不存在', 404);
        }

        // 🔒 获取图片路径字段（假设字段叫 frameImage）
        $frameImage = $frame['frameImage'] ?? null;

        // 1. 先删除数据库记录（或软删除）
        $fields = Db::getTableFields($table);
        if (in_array('delete_time', $fields, true)) {
            // 软删除
            Db::name($table)->where('id', $id)->update([
                'delete_time' => date('Y-m-d H:i:s')
            ]);
        } else {
            // 物理删除
            Db::name($table)->delete($id);
        }

        // 2. 如果有图片路径，尝试删除磁盘上的文件

        if (!empty($frameImage)) {
                $this->deleteOldAvatar($frameImage);
        }

        return success(null, '动画帧删除成功');
    }

    /**
     * 删除旧头像文件
     */
    private function deleteOldAvatar($oldAvatarPath)
    {
        if (!empty($oldAvatarPath)) {
            try {
                $pathToDelete = $oldAvatarPath;

                // 如果路径以 'storage/' 开头，需要去除
                if (str_starts_with($oldAvatarPath, 'storage/')) {
                    $pathToDelete = substr($oldAvatarPath, 8);
                }

                // 也可以检查文件是否存在再删除
                if (Filesystem::disk('public')->has($pathToDelete)) {
                    Filesystem::disk('public')->delete($pathToDelete);
                } else {
                    \think\facade\Log::warning('旧头像文件不存在: ' . $pathToDelete);
                }
            } catch (\Exception $e) {
                \think\facade\Log::error('删除动画帧图片失败: ' . $e->getMessage());
            }
        }
    }

    
    /**
     * 获取某个动画的所有动作
     * GET /api/v1/GlobalResources/GetAnimationActions
     *
     * 参数：
     *   animation_id  int  必选
     */
    public function GetAnimationActions()
    {
        $animation_id = (int) Request::param('animation_id', 0);
        if ($animation_id <= 0) {
            return error('动画ID不能为空', 400);
        }

        $list = Db::name('animation_actions')
            ->where('animation_id', $animation_id)
            ->select()
            ->toArray();
            
        foreach ($list as &$item) {
            $animationFramesList = Db::name('animation_frames')
                ->where('animation_action_id', $item['id'])
                ->order('sort', 'asc')
                ->select()
                ->toArray();
            $item['animationFramesList'] = $animationFramesList;
        }
        unset($item);

        return success($list, '获取成功');
    }

    /**
     * 上传/更新动画（animations 表）
     * POST /api/v1/GlobalResources/UploadAnimationAction
     *
     * 表单字段：
     *   id             int     可选    传 0 或不传=新增；传 id=更新
     *   name           string  可选    动画名称
     *   animation_id   int     必填    动画ID
     *   duration       int     可选    动作时长
     */
    public function UploadAnimationAction()
    {
        $user = request()->user;
        if (empty($user['id'])) {
            return error('未获取到用户信息', 401);
        }

        $id             = (int) Request::post('id', 0);
        $name           = Request::post('name', '新动作');
        $animation_id   = (int) Request::post('animation_id', 0);
        $loop       = (int) Request::post('loop', 1);
        $playOnAwake       = (int) Request::post('playOnAwake', 1);
        $duration       = (int) Request::post('duration', 100);

        if (empty($animation_id)) {
            return error('动画ID不能为空.', 400);
        }

        $row = [
            'name'        => $name,
            'animation_id'  => $animation_id,
            'loop'  => $loop,
            'playOnAwake'  => $playOnAwake,
            'duration'  => $duration,
            'update_time' => date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            // 判断动画是否存在，且属于该项目
            $exists = Db::name('animation_actions')
                ->where('id', $id)
                ->find();
            if (!$exists) {
                return error('动画不存在或无权限', 404);
            }
            Db::name('animation_actions')->where('id', $id)->update($row);
            $row['id'] = $id;
        } else {
            $row['create_time'] = date('Y-m-d H:i:s');
            $row['id'] = Db::name('animation_actions')->insertGetId($row);
        }

        return success($row, $id > 0 ? '更新成功' : '添加成功');
    }
    
    /**
     * 删除动画（专用于 animation 表）
     * Delete /api/v1/editor/DeleteAnimationAction
     * 
     * 表单参数：
     *   id int 必须，动画ID
     */
    public function DeleteAnimationAction()
    {
        $id = (int) Request::get('id'); // ✅ 从 GET 参数获取，因为是 URL query

        if (empty($id) || $id <= 0) {
            return error('动画ID必须为有效正整数', 400);
        }

        $table = 'animation_actions';

        $animation = Db::name($table)->find($id);
        if (!$animation) {
            return error('动画不存在', 404);
        }
        // 获取指定动画动作的所有帧图片路径
        $AnimationFrames = Db::name('animation_frames')
            ->where('animation_action_id', $id)
            ->field('frameImage')
            ->select();
        
        // 遍历并删除每个帧图片
        foreach ($AnimationFrames as $frame) {
            $frameImage = $frame['frameImage'];
            if (!empty($frameImage)) {
                $this->deleteOldAvatar($frameImage);
            }
        }

        $result = Db::name($table)->where('id', $id)->delete();

        if ($result) {
            return success(null, '动画删除成功');
        } else {
            return error('动画删除失败', 500);
        }
    }

}
