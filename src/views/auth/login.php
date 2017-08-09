<?php

/* @var $this  yii\web\View */
/* @var $form  yii\bootstrap\ActiveForm */
/* @var $model setrun\sys\forms\user\LoginForm */

use yii\bootstrap\ActiveForm;

$this->title = Yii::t('setrun/user', 'Authorization form');
$this->params['breadcrumbs'][] = $this->title;
?>

<?php $this->beginContent('@setrun/user/views/layouts/auth.php'); ?>

    <p class="login-box-msg">Форма авторизации</p>

<?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
<?= $form->field($model, 'username')->textInput() ?>
<?= $form->field($model, 'password')->passwordInput() ?>
    <div class="row">
        <div class="col-xs-8">
            <?= $form->field($model, 'rememberMe')->checkbox() ?>
        </div>
        <div class="col-xs-4">
            <button type="submit" class="btn btn-primary btn-block btn-flat"><?= Yii::t('setrun/user', 'Sign In'); ?></button>
        </div>
    </div>
<?php ActiveForm::end(); ?>

<?php $this->endContent(); ?>