<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

return [
    'components' => [
        'i18n' => [
            'translations' => [
                'setrun/user' => [
                    'class'    => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@setrun/user/messages',
                    'fileMap' => [
                        'setrun/user' => 'user.php',

                    ]
                ]
            ]
        ],
    ],
    'modules' => [
        'user' => 'setrun\user\Module'
    ]
];