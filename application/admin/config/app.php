<?php

return [
    /**
     * rbac 请求白名单  [route地址/action]
     */

    'rbac_white' => [
        '/admin/index/index',
        '/admin/index/logout',
        '/admin/index/error'
    ],

    'menu_icon' => [
        '权限管理' => 'fa fa-tasks',
        '会员管理' => 'fa fa-user',
        '系统配置' => 'fa fa-gears',
        '产品管理' => 'fa fa-windows',
        '交易市场' => 'fa fa-reorder',
        '内容管理' => 'fa fa-envelope',
        '菜单管理' => 'fa fa-reorder',
        '法币交易' => 'fa fa-reorder',
        '币币交易' => 'fa fa-reorder',
        '钱包管理' => 'fa fa-reorder',
        'FOMO管理' => 'fa fa-reorder',
    ],
];