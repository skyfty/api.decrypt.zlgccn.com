<?php

namespace app\model\Panel\dialogBox;

use think\Model;

class DialogBoxOption extends Model
{
    protected $table = 'panel_dialog_box_option';
    
    protected $pk = 'id';
    
    protected $type = [
        'id'                   => 'integer',
    ];
    
    // 隐藏字段
    protected $hidden = [ 'create_time', 'update_time'];
    
}