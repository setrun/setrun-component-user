<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

namespace setrun\user\commands;

use yii\helpers\Console;
use yii\console\Controller;
use setrun\user\entities\User;
use setrun\user\repositories\UserRepository;

/**
 * Class UserAbstractController.
 */
abstract class UserAbstractController extends Controller
{
    /**
     * @var UserRepository
     */
    protected $repository;

    /**
     * UserAbstractController constructor.
     * @param string $id
     * @param \yii\base\Module $module
     * @param UserRepository $repository
     * @param array $config
     */
    public function __construct($id, $module, UserRepository $repository, array $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->repository = $repository;

    }

    /**
     * Find user.
     * @param string $username
     * @throws \yii\console\Exception
     * @return User the User loaded model
     */
    protected function findModel(string $username) : User
    {
        return $this->repository->getByUsernameOrEmail($username);
    }

    /**
     * Get the value of the console.
     * @param User   $user
     * @param string $attribute
     * @return void
     */
    protected function readValue(User $user, string $attribute) : void
    {
        $user->$attribute = $this->prompt(mb_convert_case($attribute, MB_CASE_TITLE, 'utf-8') . ':', [
            'validator' => function ($input, &$error) use ($user, $attribute) {
                $user->$attribute = $input;
                if ($user->validate([$attribute])) {
                    return true;
                } else {
                    $error = implode(',', $user->getErrors($attribute));
                    return false;
                }
            },
        ]);
    }

    /**
     * Console log.
     * @param bool $success
     * @return void
     */
    protected function log(bool $success) : void
    {
        if ($success) {
            $this->stdout('Success!', Console::FG_GREEN, Console::BOLD);
        } else {
            $this->stderr('Error!', Console::FG_RED, Console::BOLD);
        }
        $this->stdout(PHP_EOL);
    }
}