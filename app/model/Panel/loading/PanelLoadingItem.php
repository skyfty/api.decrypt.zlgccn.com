<?php

namespace app\model\Panel\loading;

use think\Model;

class PanelLoadingItem extends Model
{
    protected $table = 'panel_loading_item';

    protected $pk = 'id';

    protected $type = [
        'id'         => 'integer',
        'project_id' => 'integer',
    ];

    protected $hidden = ['create_time', 'update_time'];
 
}
