<?php

namespace app\model\projectRelease\ios;

use think\Model;

class IOS_authInfo extends Model
{
    protected $table = 'IOS_authInfo';
    
    protected $pk = 'id';
    
    protected $type = [
        'id' => 'integer',
    ];

    protected $hidden = ['create_time', 'update_time'];
}