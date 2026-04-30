<?php

namespace App\Repositories\Permissions;

use Spatie\Permission\Models\Permission;
use App\Models\User;

class PermissionRepository implements PermissionRepositoryInterface
{
    protected $model;

    public function __construct(Permission $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $permission = $this->find($id);
        $permission->update($data);
        return $permission;
    }

    public function delete($id)
    {
        return $this->model->findOrFail($id)->delete();
    }

    public function assignPermissionToUser($userId, $permissionId)
    {
        $user = User::findOrFail($userId);
        $permission = $this->find($permissionId);
        return $user->givePermissionTo($permission);
    }

    public function removePermissionFromUser($userId, $permissionId)
    {
        $user = User::findOrFail($userId);
        $permission = $this->find($permissionId);
        return $user->revokePermissionTo($permission);
    }

    public function getUserPermissions($userId)
    {
        $user = User::findOrFail($userId);
        return $user->permissions;
    }
} 