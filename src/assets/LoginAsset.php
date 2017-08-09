<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

namespace setrun\user\assets;

use setrun\sys\components\web\AssetBundle;

class LoginAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@setrun/user/assets/dist';

    /**
     * @inheritdoc
     */
    public $css = [
        '//fonts.googleapis.com/css?family=Open+Sans:400,400i,300,700',
        '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css',
        'css/login.css'
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapPluginAsset'
    ];
}