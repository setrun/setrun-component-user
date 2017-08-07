<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

use setrun\user\components\Rbac;

return [
    'rules' => [

    ],
    'roles' => [
        Rbac::R_ADMINISTRATOR => [
            'name' => Rbac::R_ADMINISTRATOR,
            'description' => 'Administrator',
            'child' => [
                'roles' => [
                    Rbac::R_EDITOR
                ]
            ]

        ],
        Rbac::R_EDITOR => [
            'name' => Rbac::R_EDITOR,
            'description' => 'Editor',
            'child' => [
                'permissions' => [
                    Rbac::P_BACKEND_ACCESS
                ],
                'roles' => [
                    Rbac::R_USER
                ]
            ]
        ],
        Rbac::R_USER => [
            'name' => Rbac::R_USER,
            'description' => 'User'
        ],
    ],
    'permissions' => [
        Rbac::P_BACKEND_ACCESS => [
            'name' => Rbac::P_BACKEND_ACCESS,
            'description' => 'Backend access'
        ]
    ]
];