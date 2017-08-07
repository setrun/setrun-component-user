<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

namespace setrun\user;

use Yii;

/**
 * Class Module.
 */
class Module extends \yii\base\Module
{
    public const VERSION = '1.0';

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'setrun\user\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (Yii::$app instanceof \yii\console\Application) {
            $this->controllerNamespace = 'setrun\user\commands';
        }
    }
}