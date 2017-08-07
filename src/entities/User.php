<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

namespace setrun\user\entities;

use Yii;
use yii\helpers\Json;
use yii\db\ActiveRecord;
use setrun\sys\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;
use setrun\user\interfaces\HybridManagerInterface;

/**
 * This is the model class for table "{{%sys_user}}".
 * @property integer $id
 * @property string  $username
 * @property string  $auth_key
 * @property string  $password_hash
 * @property string  $password_reset_token
 * @property string  $email
 * @property string  $email_confirm_token
 * @property integer $status
 * @property string  $role
 * @property integer $created_at
 * @property integer $updated_at
 */
class User extends ActiveRecord implements HybridManagerInterface
{
    public const STATUS_BLOCKED = 0;
    public const STATUS_ACTIVE  = 1;
    public const STATUS_WAIT    = 2;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'                   => 'ID',
            'auth_key'             => Yii::t('sys/user', 'Auth Key'),
            'username'             => Yii::t('sys/user', 'Username'),
            'password_hash'        => Yii::t('sys/user', 'Password Hash'),
            'password_reset_token' => Yii::t('sys/user', 'Password Reset Token'),
            'email'                => Yii::t('sys/user', 'Email'),
            'email_confirm_token'  => Yii::t('sys/user', 'Email Confirm Token'),
            'status'               => Yii::t('sys/user', 'Status'),
            'role'                 => Yii::t('sys/user', 'Role'),
            'created_at'           => Yii::t('sys/user', 'Created At'),
            'updated_at'           => Yii::t('sys/user', 'Updated At'),
        ];
    }

    /**
     * Get the status of the name.
     * @return null|string
     */
    public function getStatusName() : ?string
    {
        return ArrayHelper::getValue(self::getStatusesArray(), $this->status);
    }

    /**
     * Get all statuses.
     * @return array
     */
    public static function getStatusesArray() : array
    {
        return [
            self::STATUS_BLOCKED => Yii::t('sys/user', 'Blocked'),
            self::STATUS_ACTIVE  => Yii::t('sys/user', 'Active'),
            self::STATUS_WAIT    => Yii::t('sys/user', 'Wait')
        ];
    }

    /**
     * Creating a user.
     * @param string $username
     * @param string $email
     * @param string $password
     * @return User
     */
    public static function create(string $username, string $email, string $password) : User
    {
        $user = new User();
        $user->username = $username;
        $user->email    = $email;
        $user->status   = self::STATUS_ACTIVE;
        $user->setPassword($password);
        $user->generateAuthKey();
        return $user;
    }

    /**
     * Editing a user.
     * @param string $username
     * @param string $email
     * @return void
     */
    public function edit(string $username, string $email) : void
    {
        $this->username = $username;
        $this->email    = $email;
    }

    /**
     * Request to sing up a new user.
     * @param string $username
     * @param string $email
     * @param string $password
     * @return User
     */
    public static function requestSignup(string $username, string $email, string $password) : User
    {
        $user = new User();
        $user->username = $username;
        $user->email    = $email;
        $user->status   = self::STATUS_WAIT;
        $user->generateEmailConfirmToken();
        $user->generateAuthKey();
        $user->setPassword($password);
        return $user;
    }

    /**
     * Confirm user sing up.
     * @return void
     */
    public function confirmSignup() : void
    {
        if (!$this->isWait()) {
            throw new \DomainException(Yii::t('sys/user', 'User is already active'));
        }
        $this->status = self::STATUS_ACTIVE;
        $this->removeEmailConfirmToken();
    }

    /**
     * Request to reset the user's password.
     * @return void
     */
    public function requestPasswordReset() : void
    {
        if (!empty($this->password_reset_token) && self::isPasswordResetTokenValid($this->password_reset_token)) {
            throw new \DomainException(Yii::t('sys/user', 'Password resetting is already requested'));
        }
        $this->generatePasswordResetToken();
    }

    /**
     * Reset user password.
     * @param string $password
     * @return void
     */
    public function resetPassword(string $password) : void
    {
        if (empty($this->password_reset_token)) {
            throw new \DomainException(Yii::t('sys/user', 'Password resetting is not requested'));
        }
        $this->setPassword($password);
        $this->removePasswordResetToken();
    }


    /**
     * User not active.
     * @return bool
     */
    public function isWait() : bool
    {
        return $this->status === self::STATUS_WAIT;
    }

    /**
     * User active.
     * @return bool
     */
    public function isActive() : bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * User blocked.
     * @return bool
     */
    public function isBlocked() : bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    /**
     * Finds user by username.
     * @param string $username
     * @return self
     */
    public static function findByUsername(string $username) : ?self
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token.
     * @param string $token password reset token.
     * @return self
     */
    public static function findByPasswordResetToken(string $token) : ?self
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }
        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid.
     * @param string $token password reset token.
     * @return bool
     */
    public static function isPasswordResetTokenValid(string $token) : bool
    {
        if (empty($token)) {
            return false;
        }
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire    = 3600;
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getAuthRoleNames() : array
    {
        return (array) Json::decode($this->role);
    }

    /**
     * @inheritdoc
     */
    public function setAuthRoleNames(array $roles) : void
    {
        $this->updateAttributes(['role' => (string) Json::encode($roles)]);
    }

    /**
     * @inheritdoc
     */
    public function addAuthRoleName(string $role) : void
    {
        $roles = $this->getAuthRoleNames();
        $roles[] = $role;
        $this->setAuthRoleNames($roles);
    }

    /**
     * @inheritdoc
     */
    public function removeAuthRoleName(string $role) : void
    {
        $roles = $this->getAuthRoleNames();
        $roles = array_diff($roles, [$role]);
        $this->setAuthRoleNames($roles);
    }

    /**
     * @inheritdoc
     */
    public function clearAuthRoleNames() : void
    {
        $this->setAuthRoleNames([]);
    }

    /**
     * @inheritdoc
     */
    public static function findAuthIdsByRoleName(string $roleName) : ?array
    {
        return static::find()->where(['LIKE', 'role' => $roleName])->select('id')->column();
    }

    /**
     * Generates a new password reset token.
     * @return void
     */
    public function generatePasswordResetToken() : void
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Generates a new email confirm token.
     * @return void
     */
    public function generateEmailConfirmToken() : void
    {
        $this->email_confirm_token = Yii::$app->security->generateRandomString();
    }

    /**
     * Removes a password reset token.
     * @return void
     */
    public function removePasswordResetToken() : void
    {
        $this->password_reset_token = null;
    }


    /**
     * Removes a password reset token.
     * @return void
     */
    public function removeEmailConfirmToken() : void
    {
        $this->email_confirm_token = null;
    }

    /**
     * Validates a password.
     * @param  string $password password to validate.
     * @return bool if password provided is valid for current user.
     */
    public function validatePassword(string $password) : bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model.
     * @param string $password
     * @return void
     */
    public function setPassword(string $password) : void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key.
     * @return void
     */
    public function generateAuthKey() : void
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }
}
