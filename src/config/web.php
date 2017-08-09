<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

return [
    'components' => [
        'user' => [
            'identityClass' => 'setrun\sys\components\Identity',
            'enableAutoLogin' => true,
            'loginUrl' => ['user/auth/login'],
        ],
        'urlManager' => [
            'rules' => [
                "<_a:(login|logout)>" => "user/auth/<_a>",
            ]
        ]
    ]
];