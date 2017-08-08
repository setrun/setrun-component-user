<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

namespace setrun\user\commands;

use Yii;
use yii\rbac\Role;
use yii\rbac\Rule;
use yii\helpers\Json;
use yii\helpers\Console;
use yii\rbac\Permission;
use setrun\user\components\Rbac;
use setrun\sys\helpers\ArrayHelper;
use setrun\user\components\rbac\HybridManager;

/**
 * Interactive console rbac manager.
 */
class RbacController extends UserAbstractController
{
    /**
     * @var HybridManager
     */
    protected $authManager;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->authManager = Yii::$app->getAuthManager();
        parent::init();
    }

    /**
     * Adds role to user.
     * @return void
     */
    public function actionRoleAssign() : void
    {
        $username = $this->prompt('Username:', ['required' => true]);
        $user = $this->findModel($username);
        $roleName = $this->select('Role:', ArrayHelper::map($this->authManager->getRoles(), 'name', 'description'));
        $role = $this->authManager->getRole($roleName);
        $this->authManager->assign($role, $user->id);
        $this->log(true);
    }

    /**
     * Removes role from user.
     * @return void
     */
    public function actionRoleRevoke() : void
    {
        $username = $this->prompt('Username:', ['required' => true]);
        $user = $this->findModel($username);
        $roleName = $this->select('Role:', ArrayHelper::merge(
            ['all' => 'All Roles'],
            ArrayHelper::map($this->authManager->getRolesByUser($user->id), 'name', 'description'))
        );
        if ($roleName == 'all') {
            $this->authManager->revokeAll($user->id);
        } else {
            $role = $this->authManager->getRole($roleName);
            $this->authManager->revoke($role, $user->id);
        }
        $this->log(true);
    }

    /**
     * Generates default roles.
     * @return void
     */
    public function actionInit() : void
    {
        $this->initSURole();
        $config = $this->loadConfig();
        $this->applyConfig($config);
        $this->applyAssignConfig($config);
        $this->addAssignmentsToSu();
        $this->log(true);
    }

    /**
     * Initialize the superuser role.
     * @return void
     */
    protected function initSURole() : void
    {
        $this->stdout("Init SU role \n",  Console::FG_YELLOW);
        $this->authManager->removeAll();
        $su = $this->authManager->createRole(Rbac::R_SU);
        $su->description = 'Super User';
        $this->authManager->add($su);
    }

    /**
     * Load configurations of rbac.
     * @return array
     */
    protected function loadConfig() : array
    {
        $modulesPath = ROOT_DIR . '/modules';
        $config = [];
        /** @var $item \SplFileInfo  */
        foreach (new \GlobIterator($modulesPath . '/*/config/rbac.php') as $item) {
            $module = require $item->getRealPath();
            if ($module instanceof \Closure) {
                $config = $module($config);
            } else if(is_array($module)) {
                $config = array_replace_recursive($config, $module);
            }
        }
        return $config;
    }

    /**
     * Apply received config for rbac.
     * @param array $config
     * @return void
     */
    protected function applyConfig(array $config) : void
    {
        $this->applyRules(ArrayHelper::get($config, 'rules', []));
        $this->applyRoles(ArrayHelper::get($config, 'roles', []));
        $this->applyPermissions(ArrayHelper::get($config, 'permissions', []));
    }

    /**
     * Apply assignments.
     * @param array $config
     * @return void
     */
    protected function applyAssignConfig(array $config) : void
    {
        $this->assignRoles(ArrayHelper::get($config, 'roles', []));
        $this->assignPermissions(ArrayHelper::get($config, 'permissions', []));
    }

    /**
     * Apply assignments to SU role.
     * @return void
     */
    protected function addAssignmentsToSu() : void
    {
        $roleSu = $this->authManager->getRole(Rbac::R_SU);
        $this->addRolesToSU($roleSu);
        $this->addPermissionsToSU($roleSu);
    }

    /**
     * Apply assignment rules.
     * @param array $config
     * @return void
     */
    protected function applyRules(array $config) : void
    {
        $this->stdout("Init rules: " . count($config) . "\n", Console::FG_YELLOW);
        foreach ($config as $item) {
            if ($rule = $this->applyRule($item)) {
                $this->stdout("\t- success: " . $rule->name . "\n", Console::FG_GREEN);
            } else {
                $this->stdout("\t- error config rule: " . Json::encode($item) . "\n", Console::FG_RED);
            }
        }
    }

    /**
     * Apply assignment roles.
     * @param array $config
     * @return void
     */
    protected function applyRoles(array $config) : void
    {
        $this->stdout("Init roles: " . count($config) . "\n", Console::FG_YELLOW);
        foreach ($config as $item) {
            if ($role = $this->applyRole($item)) {
                $this->stdout("\t- success: " . $role->name . "\n", Console::FG_GREEN);
            } else {
                $this->stdout("\t- error config role: " . Json::encode($item) . "\n", Console::FG_RED);
            }
        }
    }

    /**
     * Apply assignment permissions.
     * @param array $config
     * @return void
     */
    protected function applyPermissions(array $config) : void
    {
        $this->stdout("Init permissions: " . count($config) . "\n", Console::FG_YELLOW);
        foreach ($config as $item) {
            if ($permission = $this->applyPermission($item)) {
                $this->stdout("\t- success: " . $permission->name . "\n", Console::FG_GREEN);
            } else {
                $this->stdout("\t- error config permission: " . Json::encode($item) . "\n", Console::FG_RED);
            }
        }
    }

    /**
     * Apply assignment permission.
     * @param array $data
     * @return bool|Permission
     */
    protected function applyPermission(array $data)
    {
        if (!($name = ArrayHelper::get($data, 'name'))) {
            return false;
        }
        if ($permission = $this->authManager->getPermission($name)) {
            return $permission;
        }
        $permission = $this->authManager->createPermission($name);
        $permission->description = ArrayHelper::get($data, 'description', '');
        $permission->ruleName    = ArrayHelper::get($data, 'ruleName',    '');
        if ($this->authManager->add($permission)) {
            return $permission;
        }
        return false;
    }

    /**
     * Apply assignment role.
     * @param array $data
     * @return bool|null|Role
     */
    protected function applyRole(array $data)
    {
        if (!($name = ArrayHelper::get($data, 'name'))) {
            return false;
        }
        if ($role = $this->authManager->getRole($name)) {
            return $role;
        }
        $role = $this->authManager->createRole($name);
        $role->description = ArrayHelper::get($data, 'description', '');
        if ($this->authManager->add($role)) {
            return $role;
        }
        return false;
    }

    /**
     * Apply assignment rule.
     * @param array $data
     * @return bool|null|Rule
     */
    protected function applyRule(array $data)
    {
        if (!($class = ArrayHelper::get($data, 'class')) || !(class_exists($class))) {
            return false;
        }
        $rule = new $class;
        if (!$rule instanceof Rule) {
            return false;
        }
        if ($ruleExist = $this->authManager->getRule($rule->name)) {
            return $ruleExist;
        }
        if ($this->authManager->add($rule)) {
            return $rule;
        }
        return false;
    }


    /**
     * Apply assignment all roles.
     * @param array $config
     * @return void
     */
    protected function assignRoles(array $config) : void
    {
        $this->stdout("Assign roles: " . count($config) . "\n", Console::FG_YELLOW);
        foreach ($config as $item) {
            if (!$role = $this->assignRole($item)) {
                $this->stdout("\t- error assigned role: " . Json::encode($item) . "\n", Console::FG_RED);
            }
        }
    }

    /**
     * Apply assignment all permissions.
     * @param array $config
     * @return void
     */
    protected function assignPermissions(array $config) : void
    {
        $this->stdout("Assign permissions: " . count($config) . "\n", Console::FG_YELLOW);
        foreach ($config as $item) {
            if (!$permission = $this->assignPermission($item)) {
                $this->stdout("\t- error assigned permission: " . Json::encode($item) . "\n", Console::FG_RED);
            }
        }
    }

    /**
     * Apply assignment role.
     * @param array $config
     * @return bool|null|Role
     */
    protected function assignRole(array $config)
    {
        if (!($name = ArrayHelper::get($config, 'name')) || !($role  = $this->authManager->getRole($name))){
            return false;
        }
        if (!($child = ArrayHelper::get($config, 'child'))) {
            return $role;
        }
        if ($childRoles = ArrayHelper::get($child, 'roles')) {
            foreach ($childRoles as $name) {
                if ($roleChild = $this->authManager->getRole($name)) {
                    try {
                        $this->authManager->addChild($role, $roleChild);
                        $this->stdout("\tAssign child role: {$name} - success\n", Console::FG_GREEN);
                    } catch(\Exception $e) {
                        $this->stdout("\t- already exist\n");
                    }
                }
            }
        }
        if ($childPermissions = ArrayHelper::get($child, 'permissions')) {
            foreach ($childPermissions as $name) {
                if ($permissionChild = $this->authManager->getPermission($name)) {
                    try {
                        $this->authManager->addChild($role, $permissionChild);
                        $this->stdout("\tAssign child permission: {$name} - success\n", Console::FG_GREEN);
                    } catch(\Exception $e) {
                        $this->stdout("\t- already exist\n");
                    }
                }
            }
        }
        return $role;
    }

    /**
     * Apply assignment permission.
     * @param array $config
     * @return bool|null|Permission
     */
    protected function assignPermission(array $config)
    {
        if (!($name = ArrayHelper::get($config, 'name')) || !($permission = $this->authManager->getPermission($name))) {
            return false;
        }
        if (!($child = ArrayHelper::get($config, 'child'))){
            return $permission;
        }
        if ($childRoles = ArrayHelper::get($child, 'roles', false)) {
            foreach ($childRoles as $name) {
                if ($roleChild = $this->authManager->getRole($name)) {
                    try {
                        $this->authManager->addChild($permission, $roleChild);
                        $this->stdout("\tAssign child role: {$name} - success\n", Console::FG_GREEN);
                    } catch(\Exception $e) {
                        $this->stdout("\t- already exist\n", Console::FG_RED);
                    }
                }
            }
        }
        if ($childPermissions = ArrayHelper::get($child, 'permissions')) {
            foreach ($childPermissions as $name) {
                if ($permissionChild = $this->authManager->getPermission($name)) {
                    try {
                        $this->authManager->addChild($permission, $permissionChild);
                        $this->stdout("\tAssign child permission: {$name} - success\n", Console::FG_GREEN);
                    } catch(\Exception $e) {
                        $this->stdout("\t- already exist\n", Console::FG_RED);
                    }
                }
            }
        }
        return $permission;
    }

    /**
     * Added all assignment roles to role SU.
     * @param Role $roleSu
     */
    protected function addRolesToSU(Role $roleSu)
    {
        $this->stdout("Init SU assigning roles\n",  Console::FG_YELLOW);
        foreach ($this->authManager->getRoles() as $role) {
            try {
                if ($role->name !== $roleSu->name) {
                    $this->authManager->addChild($roleSu, $role);
                    $this->stdout("\tAssign to SU role: {$role->name} - success\n", Console::FG_GREEN);
                }
            } catch(\Exception $e) {
                $this->stdout("\t- already exist\n",  Console::FG_RED);
            }
        };
    }

    /**
     * Added all assignment permissions to role SU.
     * @param Role $roleSu
     */
    protected function addPermissionsToSU(Role $roleSu)
    {
        $this->stdout("Init SU assigning permissions\n",  Console::FG_YELLOW);
        foreach ($this->authManager->getPermissions() as $permission) {
            try {
                $this->authManager->addChild($roleSu, $permission);
                $this->stdout("\tAssign to SU permission: {$permission->name} - success\n", Console::FG_GREEN);
            } catch(\Exception $e) {
                $this->stdout("\t- already exist\n", Console::FG_RED);
            }
        };
    }
}