<?php

namespace app\model\Panel\dialogBox;

use think\Model;

class DialogBoxRole extends Model
{
    protected $table = 'panel_dialog_box_role';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'                   => 'integer'
    ];
    
    // 隐藏字段
    protected $hidden = [ 'create_time', 'update_time'];
    
}