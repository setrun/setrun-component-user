<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

namespace setrun\user\controllers;

use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use setrun\user\forms\LoginForm;
use setrun\user\components\Identity;
use setrun\user\services\AuthService;
use setrun\sys\components\controllers\FrontController;

/**
 * Class UserController.
 */
class AuthController extends FrontController
{
    /**
     * @var AuthService
     */
    private $service;

    /**
     * AuthController constructor.
     * @param string $id
     * @param \yii\base\Module $module
     * @param AuthService $service
     * @param array $config
     */
    public function __construct($id, $module, AuthService $service, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->service = $service;
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ]
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['POST']
                ]
            ]
        ];
    }

    /**
     * Logs in a user.
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $form = new LoginForm();
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $user = $this->service->auth($form);
                Yii::$app->user->login(new Identity($user),$form->rememberMe ? AuthService::REMEMBER : 0);
                return $this->goBack();
            } catch (\DomainException $e) {
                Yii::$app->errorHandler->logException($e);
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }
        return $this->renderPartial('login', [
            'model' => $form,
        ]);
    }

    /**
     * Logs out the current user.
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }
}