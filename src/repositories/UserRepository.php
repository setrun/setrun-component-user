<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

namespace setrun\user\repositories;

use setrun\user\entities\User;
use setrun\sys\interfaces\i18nInterface;
use setrun\sys\exceptions\NotFoundException;

/**
 * Class UserRepository.
 */
class UserRepository
{
    /**
     * @var i18nInterface
     */
    protected $i18n;

    /**
     * UserRepository constructor.
     * @param i18nInterface $i18n
     */
    public function __construct(i18nInterface $i18n)
    {
        $this->i18n = $i18n;
    }

    /**
     * Find a user.
     * @param string|int $id
     * @return null|User
     */
    public function find($id) : ?User
    {
        return $this->findBy(['id' => $id]);
    }

    /**
     * Find a user by name.
     * @param string $username
     * @return null|User
     */
    public function findActiveByUsername(string $username) : ?User
    {
        return $this->findBy(['username' => $username, 'status' => User::STATUS_ACTIVE]);
    }

    /**
     * Find for a user by name or email.
     * @param string $value
     * @return User|null
     */
    public function findByUsernameOrEmail(string $value) : ?User
    {
        return $this->findBy(['or', ['username' => $value], ['email' => $value]]);
    }

    /**
     * Find a active user by ID.
     * @param string|int $id
     * @return User|null
     */
    public function findActiveById($id) : ?User
    {
        return $this->findBy(['id' => $id, 'status' => User::STATUS_ACTIVE]);
    }

    /**
     * Get a user by ID.
     * @param string|int $id
     * @return User
     */
    public function get($id) : User
    {
        return $this->getBy(['id' => $id]);
    }

    /**
     * Get a user by email confirmed token.
     * @param string $token
     * @return User
     */
    public function getByEmailConfirmToken(string $token) : User
    {
        return $this->getBy(['email_confirm_token' => $token, 'status' => User::STATUS_ACTIVE]);
    }

    /**
     * Get a user by email.
     * @param string $email
     * @return User
     */
    public function getByEmail(string $email) : User
    {
        return $this->getBy(['email' => $email]);
    }

    /**
     * Get a user by password reset token.
     * @param string $token
     * @return User
     */
    public function getByPasswordResetToken(string $token) : User
    {
        return $this->getBy(['password_reset_token' => $token]);
    }

    /**
     * Get for a user by name or email.
     * @param string $value
     * @return User
     */
    public function getByUsernameOrEmail(string $value) : User
    {
        return $this->getBy(['or', ['username' => $value], ['email' => $value]]);
    }

    /**
     * Check exists password reset token.
     * @param string $token
     * @return bool
     */
    public function existsByPasswordResetToken(string $token) : bool
    {
        return (bool) User::findByPasswordResetToken($token);
    }

    /**
     * Save user.
     * @param User $user
     * @return bool
     */
    public function save(User $user) : bool
    {
        if (!$user->save()) {
            throw new \RuntimeException($this->i18n->t('sys/user', 'Saving error'));
        }
        return true;
    }

    /**
     * Remove user.
     * @param User $user
     * @return bool
     */
    public function remove(User $user) : bool
    {
        if (!$user->delete()) {
            throw new \RuntimeException($this->i18n->t('sys/user', 'Removing error'));
        }
        return true;
    }

    /**
     * Get user by condition.
     * @param array $condition
     * @return User|array
     */
    public function getBy(array $condition) : User
    {
        if (!$user = User::find()->andWhere($condition)->limit(1)->one()) {
            throw new NotFoundException($this->i18n->t('sys/user', 'User not found'));
        }
        return $user;
    }

    /**
     * Find user by condition.
     * @param array $condition
     * @return User|null|array
     */
    public function findBy(array $condition) : ?User
    {
        return User::find()->andWhere($condition)->limit(1)->one();
    }
}