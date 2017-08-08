<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

namespace setrun\гыук\components\user;

use Yii;
use setrun\user\entities\User;
use yii\web\IdentityInterface;
use yii\base\NotSupportedException;
use setrun\user\repositories\UserRepository;

class Identity implements IdentityInterface
{
    /**
     * @var User
     */
    private $user;

    /**
     * Identity constructor.
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->checkUserInfo();
    }

    /**
     * Finds an identity by the given ID.
     * @param  string|int $id
     * @return null|Identity
     */
    public static function findIdentity($id) : ?Identity
    {
        $user = self::getRepository()->findActiveById($id);
        return $user ? new self($user) : null;
    }

    /**
     * Finds an identity by the given token.
     * @param  string $token
     * @param  null   $type
     * @throws NotSupportedException
     */
    public static function findIdentityByAccessToken($token, $type = null) : void
    {
        throw new NotSupportedException(Yii::t('sys', 'findIdentityByAccessToken is not implemented'));
    }

    /**
     * Get a user ID.
     * @return int|string current user ID
     */
    public function getId()
    {
        return $this->user->id;
    }

    /**
     * Get a user entity.
     * @return User
     */
    public function getUser() : User
    {
        return $this->user;
    }

    /**
     * Get a user user key.
     * @return string current user user key
     */
    public function getAuthKey() : string
    {
        return $this->user->auth_key;
    }

    /**
     * Check a user user key.
     * @param string $authKey
     * @return bool if user key is valid for current user
     */
    public function validateAuthKey($authKey) : bool
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Init user info.
     * @return void
     */
    private function checkUserInfo() : void
    {
        foreach ($this->user->getAttributes() as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Get a user repository.
     * @return UserRepository
     */
    private static function getRepository() : UserRepository
    {
        return Yii::$container->get(UserRepository::class);
    }
}