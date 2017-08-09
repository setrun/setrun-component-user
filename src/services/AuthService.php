<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

namespace setrun\user\services;

use yii\web\Session;
use setrun\user\entities\User;
use setrun\user\forms\LoginForm;
use setrun\sys\services\i18nService;
use setrun\user\repositories\UserRepository;

/**
 * Class AuthService.
 */
class AuthService
{
    public const FAILURE      = 3;
    public const FAILURE_TIME = (60 * 5);
    public const REMEMBER     = (60 * 60 * 24 * 30);

    /**
     * @var UserRepository
     */
    private $user;

    /**
     * @var i18nService
     */
    private $i18n;

    /**
     * @var Session
     */
    private $session;

    /**
     * AuthService constructor.
     * @param UserRepository $user
     * @param i18nService    $i18n
     */
    public function __construct(UserRepository $user, i18nService $i18n, Session $session)
    {
        $this->user    = $user;
        $this->i18n    = $i18n;
        $this->session = $session;
    }

    /**
     * User auth.
     * @param LoginForm $form
     * @return User
     */
    public function auth(LoginForm $form): User
    {
        $this->checkFailure();
        $user = $this->user->findByUsernameOrEmail($form->username);
        if (!$user || !$user->validatePassword($form->password)) {
            $this->setFailure();
            throw new \DomainException($this->i18n->t('setrun/user', 'Wrong Username or Password'));
        }
        if ($user && $user->status == User::STATUS_BLOCKED) {
            throw new \DomainException($this->i18n->t('setrun/user', 'Account temporarily blocked'));
        }
        if ($user && $user->status == User::STATUS_WAIT) {
            throw new \DomainException($this->i18n->t('setrun/user', 'Account not confirmed'));
        }
        $this->removeFailure();
        return $user;
    }

    /**
     * Check for failure of access.
     * @return void
     */
    private function checkFailure() : void
    {
        $failure = (int) $this->session->get('failure', 0);
        $time    = (int) $this->session->get('failure_time', time());
        if ($failure >= static::FAILURE) {
            if ($time >= time()) {
                throw new \DomainException($this->i18n->t(
                    'setrun/user',
                    'Form is blocked for {min} minutes',
                    ['min' => static::FAILURE_TIME / 60])
                );
            }
            $this->removeFailure();
        }
    }

    /**
     * Set a failure.
     * @return void
     */
    private function setFailure() : void
    {
        $this->session->set('failure',      $this->session->get('failure') + 1);
        $this->session->set('failure_time', time() + (int) static::FAILURE_TIME);
    }

    /**
     * Remove a failure.
     * @return void
     */
    private function removeFailure() : void
    {
        $this->session->remove('failure');
        $this->session->remove('failure_time');
    }
}