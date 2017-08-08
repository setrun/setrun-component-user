<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

namespace setrun\user\commands;

use setrun\user\entities\User;

/**
 * Interactive console user manager.
 */
class UserController extends UserAbstractController
{
    /**
     * Creates new user.
     */
    public function actionCreate()
    {
        $user = new User();
        $this->readValue($user, 'username');
        $this->readValue($user, 'email');
        $user->setPassword($this->prompt('Password:', [
            'required' => true,
            'pattern'  => '#^.{6,255}$#i',
            'error'    => 'More than 6 symbols',
        ]));
        $user->generateAuthKey();
        $user->status = User::STATUS_ACTIVE;
        $this->log($this->repository->save($user));
    }

    /**
     * Removes user by username.
     */
    public function actionRemove()
    {
        $username = $this->prompt('Username:', ['required' => true]);
        $user = $this->findModel($username);
        $this->log($this->repository->remove($user));
    }

    /**
     * Activates user.
     */
    public function actionActivate()
    {
        $username = $this->prompt('Username:', ['required' => true]);
        $user = $this->findModel($username);
        $user->status = User::STATUS_ACTIVE;
        $this->log($this->repository->save($user));
    }

    /**
     * Blocked user.
     */
    public function actionBlocked()
    {
        $username = $this->prompt('Username:', ['required' => true]);
        $user = $this->findModel($username);
        $user->status = User::STATUS_BLOCKED;
        $this->log($this->repository->save($user));
    }

    /**
     * Changes user password.
     */
    public function actionChangePassword()
    {
        $username = $this->prompt('Username:', ['required' => true]);
        $user = $this->findModel($username);
        $user->setPassword($this->prompt('New password:', [
            'required' => true,
            'pattern' => '#^.{6,255}$#i',
            'error' => 'More than 6 symbols',
        ]));
        $this->log($this->repository->save($user));
    }
}