<?php

/**
 * @author Denis Utkin <dizirator@gmail.com>
 * @link   https://github.com/dizirator
 */

namespace setrun\user\interfaces;

/**
 * Interface HybridManagerInterface.
 */
interface HybridManagerInterface
{
    /**
     * Get list roles of user.
     * @return array
     */
    public function getAuthRoleNames() : array;

    /**
     * Set list roles of user.
     * @param array $roles
     */
    public function setAuthRoleNames(array $roles) : void;

    /**
     * Add role for user.
     * @param string $role
     */
    public function addAuthRoleName(string $role) : void;

    /**
     * Remove role of user.
     * @param string $role
     */
    public function removeAuthRoleName(string $role) : void;

    /**
     * Remove all roles af user.
     */
    public function clearAuthRoleNames() : void;

    /**
     * Get users by role.
     * @param string $roleName
     * @return array|null
     */
    public static function findAuthIdsByRoleName(string $roleName) : ?array;
}