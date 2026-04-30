<?php

namespace App\Repositories\Roles;

use Spatie\Permission\Models\Role;
use App\Models\User;

class RoleRepository implements RoleRepositoryInterface
{
    protected $model;

    public function __construct(Role $model)
    {
        $this->model = $model;
    }

    public function all($withPermissions = false)
    {
        if ($withPermissions) {
            return $this->model->with('permissions')->get();
        }

        return $this->model->get();
    }

    public function find($id, $withPermissions = false)
    {
        if ($withPermissions) {
            return $this->model->with('permissions')->findOrFail($id);
        }

        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $role = $this->find($id);
        $role->update($data);
        return $role;
    }

    public function delete($id)
    {
        return $this->model->findOrFail($id)->delete();
    }

    public function assignPermissions($roleId, array $permissionIds)
    {
        $role = $this->find($roleId);
        return $role->syncPermissions($permissionIds);
    }

    public function removePermissions($roleId, array $permissionIds)
    {
        $role = $this->find($roleId);
        return $role->revokePermissionTo($permissionIds);
    }

    public function getPermissions($roleId)
    {
        $role = $this->find($roleId, true);
        return $role->permissions;
    }

    public function assignRoleToUser($userId, $roleId)
    {
        $user = User::findOrFail($userId);
        $role = $this->find($roleId);
        return $user->assignRole($role);
    }

    public function removeRoleFromUser($userId, $roleId)
    {
        $user = User::findOrFail($userId);
        $role = $this->find($roleId);
        return $user->removeRole($role);
    }

    public function getUserRoles($userId)
    {
        $user = User::findOrFail($userId);
        return $user->roles;
    }
} 