<?php

namespace app\model\Panel\attribute;

use think\Model;

class PanelAttribute extends Model
{
    protected $table = 'panel_attribute';

    protected $pk = 'id';

    protected $type = [
        'id' => 'integer',
    ];

    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];

}
