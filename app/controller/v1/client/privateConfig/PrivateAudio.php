<?php

declare(strict_types=1);

namespace app\controller\v1\client\privateConfig;

use app\BaseController;
use think\facade\Request;
use think\facade\Db;

class PrivateAudio
{
    public function getPrivateAudios()
    {
        $buttonPoint_id = (int) Request::param('buttonPoint_id', 0);
        if ($buttonPoint_id <= 0) {
            return json(['code' => 400, 'msg' => '项目ID不能为空', 'data' => null]);
        }

        // 查询数据
        $list = Db::table('button_point_resources_audio')
            ->where('buttonPoint_id', $buttonPoint_id)
            ->withoutField(['buttonPoint_id', 'create_time', 'update_time'])
            ->select()
            ->toArray();

        // 处理音频路径
        foreach ($list as &$item) {
            $audio = Db::table('audio')
                ->where('id', $item['resource_id'])
                ->field('file')
                ->find();
            unset($item['resource_id']);
            $item['audio_path'] = $audio['file'] ?? '';
        }

        return json(['code' => 200, 'msg' => '获取成功', 'data' => $list]);
    }
}