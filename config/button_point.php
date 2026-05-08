<?php
return [
    /* 类型 => [ 表名 , 字段 => 默认值 | 闭包 ] */
    1 => [
        'table' => 'button_point_tip',
        'fields' => [
            'tipContent' => ['default' => '这是一条提示'],
        ],
    ],

    2 => [
        'table' => 'button_point_draggable',
        'fields' => [
            'DragDropReset'   => ['default' => 0],
            'DragDropRestrict'=> ['default' => 0],
            'target_button_point_id' => [
                'default' => fn($p) => ($p['DragDropRestrict'] ?? 0) == 0
                    ? ($p['target_button_point_id'] ?? 0) : null,
            ],
            'anchor' => [
                'default' => fn($p) => ($p['DragDropRestrict'] ?? 0) == 0
                    ? null : ($p['anchor'] ?? 0),
            ],
            'target_x' => [
                'default' => fn($p) => ($p['DragDropRestrict'] ?? 0) == 0
                    ? null : ($p['target_x'] ?? 0),
            ],
            'target_y' => [
                'default' => fn($p) => ($p['DragDropRestrict'] ?? 0) == 0
                    ? null : ($p['target_y'] ?? 0),
            ],
        ],
    ],

    3 => [
        'table' => 'button_point_rotate',
        'fields' => [
            'target_button_point_id' => ['default' => 0],
            'current_angle'          => ['default' => 0],
            'target_angle'           => ['default' => 0],
        ],
    ],

    4 => [
        'table' => 'button_point_move',
        'fields' => [
            'moveOrientation' => ['default' => 0],
            'moveDistance'    => ['default' => 0],
        ],
    ],

    5 => [
        'table' => 'button_point_nineSquarecalligraphyGrid',
        'fields' => [
            'blankGrid'     => ['default' => 0],
            'initOrder'     => ['default' => 0],
            'order'         => ['default' => 0],
            'paddingImage'  => ['default' => 0],
            'compoundImage' => [
                'default' => fn($p) => ($p['blankGrid'] ?? 0) == 1
                    ? ($p['compoundImage'] ?? 0) : null,
            ],
        ],
    ],
    
    6 => ['table' => '', 'fields' => []],

    7 => ['table' => '', 'fields' => []],

    8 => ['table' => '', 'fields' => []],

    9 => [
        'table' => 'button_point_door',
        'fields' => [
            'isOpen'      => ['default' => 0],
            'doorCityId'  => ['default' => 0],
            'doorRoomId'  => ['default' => 0],
            'successVoice'=> ['default' => 0],
            'errorVoice'  => ['default' => 0],
            'doorType'    => ['default' => ''],
            /* 不同门类型字段 */
            'itemsID' => [
                'default' => fn($p) => in_array($p['doorType']??'', ['BasicDoor'])
                    ? ($p['itemsID'] ?? 0) : null,
            ],
            'itemCount' => [
                'default' => fn($p) => in_array($p['doorType']??'', ['BasicDoor'])
                    ? ($p['itemCount'] ?? 0) : null,
            ],
            'lockText' => [
                'default' => fn($p) => in_array($p['doorType']??'', ['BasicDoor','NumericCodeDoor','AlphaKeyDoor','PlotDoor'])
                    ? ($p['lockText'] ?? '') : null,
            ],
            'password' => [
                'default' => fn($p) => in_array($p['doorType']??'', ['NumericCodeDoor','AlphaKeyDoor'])
                    ? ($p['password'] ?? 0) : null,
            ],
            'count' => [
                'default' => fn($p) => in_array($p['doorType']??'', ['NumericCodeDoor','AlphaKeyDoor'])
                    ? ($p['count'] ?? 0) : null,
            ],
            'plotId' => [
                'default' => fn($p) => ($p['doorType'] ?? '') === 'PlotDoor'
                    ? ($p['plotId'] ?? 0) : null,
            ],
            'plotPoint' => [
                'default' => fn($p) => ($p['doorType'] ?? '') === 'PlotDoor'
                    ? ($p['plotPoint'] ?? 0) : null,
            ],
            'moveOrientation' => [
                'default' => fn($p) => ($p['doorType'] ?? '') === 'DraggableDoor'
                    ? ($p['moveOrientation'] ?? 0) : null,
            ],
            'moveDistance' => [
                'default' => fn($p) => ($p['doorType'] ?? '') === 'DraggableDoor'
                    ? ($p['moveDistance'] ?? 0) : null,
            ],
            'pointAnchors' => [
                'default' => fn($p) => ($p['doorType'] ?? '') === 'LogicDoor'
                    ? ($p['pointAnchors'] ?? 0) : null,
            ],
            'pointX' => [
                'default' => fn($p) => ($p['doorType'] ?? '') === 'LogicDoor'
                    ? ($p['pointX'] ?? 0) : null,
            ],
            'pointY' => [
                'default' => fn($p) => ($p['doorType'] ?? '') === 'LogicDoor'
                    ? ($p['pointY'] ?? 0) : null,
            ],
        ],
    ],

    10 => [
        'table' => 'button_point_item',
        'fields' => [
            'itemsType' => ['default' => ''],
            'itemsID' => [
                'default' => fn($p) => ($p['itemsType'] ?? '') === 'PickUp'
                    ? ($p['itemsID'] ?? 0) : null,
            ],
            'itemCount' => [
                'default' => fn($p) => ($p['itemsType'] ?? '') === 'PickUp'
                    ? ($p['itemCount'] ?? 0) : null,
            ],
            'items' => [
                'default' => fn($p) => ($p['itemsType'] ?? '') === 'Preview'
                    ? ($p['items'] ?? 0) : null,
            ],
            'zoomRatio' => [
                'default' => fn($p) => ($p['itemsType'] ?? '') === 'Preview'
                    ? ($p['zoomRatio'] ?? 0) : null,
            ],
        ],
    ],
    11 => [
        'table' => 'button_point_chapter',
        'fields' => [
            'content' => ['default' => null],
            'background'    => ['default' => null],
            'type'    => ['default' => 'static'],
        ],
    ],
];