<?php

namespace app\model\Panel\itemBar;

use think\Model;

class PanelItemBarItemSlot extends Model
{
    protected $table = 'panel_itemBar_itemSlot';

    protected $pk = 'id';

    protected $type = [
        'id'                   => 'integer',
    ];

    // 隐藏字段
    protected $hidden = ['create_time', 'update_time'];

}
