<?php

namespace App\Repositories\Roles;

interface RoleRepositoryInterface
{
    public function all($withPermissions = false);
    public function find($id, $withPermissions = false);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function assignPermissions($roleId, array $permissionIds);
    public function removePermissions($roleId, array $permissionIds);
    public function getPermissions($roleId);
    public function assignRoleToUser($userId, $roleId);
    public function removeRoleFromUser($userId, $roleId);
    public function getUserRoles($userId);
} 