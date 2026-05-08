<?php

declare(strict_types=1);

namespace app\controller\v1\client\globalResources;

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

}
