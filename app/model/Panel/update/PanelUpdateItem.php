<?php

namespace app\model\Panel\update;

use think\Model;

class PanelUpdateItem extends Model
{
    protected $table = 'panel_update_item';

    protected $pk = 'id';

    protected $type = [
        'id'         => 'integer',
        'project_id' => 'integer',
    ];

    protected $hidden = ['create_time', 'update_time'];
 
}
