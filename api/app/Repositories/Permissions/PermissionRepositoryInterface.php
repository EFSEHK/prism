<?php

namespace App\Repositories\Permissions;

interface PermissionRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function assignPermissionToUser($userId, $permissionId);
    public function removePermissionFromUser($userId, $permissionId);
    public function getUserPermissions($userId);
} 