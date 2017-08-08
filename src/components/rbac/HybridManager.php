<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

namespace setrun\user\components\rbac;

use Yii;
use yii\db\ActiveRecord;
use yii\rbac\Assignment;
use yii\rbac\PhpManager;
use yii\base\InvalidParamException;
use setrun\sys\exceptions\NotFoundException;
use setrun\user\interfaces\HybridManagerInterface;

/**
 * Class HybridManager.
 */
class HybridManager extends PhpManager
{
    /**
     * @var HybridManagerInterface
     */
    public $model = 'setrun\user\entities\User';

    /**
     * @inheritdoc
     */
    public function getAssignments($uid)
    {
        $assignments = [];
        if ($uid && $user = $this->getUser($uid)) {
            foreach ($user->getAuthRoleNames() as $roleName) {
                $assignments[$roleName] = new Assignment(['userId' => $uid, 'roleName' => $roleName]);
            }
        }
        return $assignments;

    }

    /**
     * @inheritdoc
     */
    public function getAssignment($roleName, $uid)
    {
        if ($uid && $user = $this->getUser($uid)) {
            if (in_array($roleName, $user->getAuthRoleNames())) {
                return new Assignment(['userId' => $uid, 'roleName' => $roleName]);
            }
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getUserIdsByRole($roleName)
    {
        return $this->model::findAuthIdsByRoleName($roleName);
    }

    /**
     * @inheritdoc
     */
    public function assign($role, $uid)
    {
        if ($uid && $user = $this->getUser($uid)) {
            if (in_array($role->name, $user->getAuthRoleNames())) {
                throw new InvalidParamException(
                    Yii::t(
                        'sys/user',
                        'Authorization item [{role}] has already been assigned to user [{id}]',
                        [ 'role' => $role->name, 'id'   => $uid]
                    )
                );
            } else {
                $assignment = new Assignment(['userId' => $uid, 'roleName' => $role->name]);
                $user->addAuthRoleName($role->name);
                return $assignment;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function revoke($role, $uid)
    {
        if ($uid && $user = $this->getUser($uid)) {
            if (in_array($role->name, $user->getAuthRoleNames())) {
                $user->removeAuthRoleName($role->name);
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function revokeAll($uid)
    {
        if ($uid && $user = $this->getUser($uid)) {
            $user->clearAuthRoleNames();
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function loadFromFile($file) : array
    {
        if ($this->assignmentFile == $file) {
            return [];
        }
        return parent::loadFromFile($file);
    }

    /**
     * @inheritdoc
     */
    protected function saveToFile($data, $file) : void
    {
        if ($this->assignmentFile === $file) {
            return;
        }
        parent::saveToFile($data, $file);
    }

    /**
     * @param $id
     * @return array|ActiveRecord
     */
    private function getUser($id) : ActiveRecord
    {
        $user = Yii::$app->get('user', false);
        if ($user && !$user->getIsGuest() && $user->getId() == $id) {
            return $user->getIdentity()->getUser();
        }
        /* @var $user ActiveRecord */
        if (!$user = $this->model::findOne($id)) {
            throw new NotFoundException(Yii::t('setrun/sys/user', 'User not found'));
        }
        return $user;
    }
}