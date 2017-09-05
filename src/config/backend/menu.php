<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

return [
    'b-user' => [
        'label' => Yii::t('setrun/user', 'Users'),
        'icon'  => 'user-o',
        'url'   => '#',
        'items' => [
            [
                'label'      => Yii::t('setrun/user', 'Users'),
                'url'        => ['/user/backend/user/index'],
                'controller' => 'backend/user',
            ],
            [
                'label'      => Yii::t('setrun/user', 'Roles'),
                'url'        => ['/user/backend/role/index'],
                'controller' => 'backend/role',
            ],
            [
                'label'      =>Yii::t('setrun/user', 'Permissions'),
                'url'        => ['/user/backend/permission/index'],
                'controller' => 'backend/permission',
            ],

        ]
    ],
];