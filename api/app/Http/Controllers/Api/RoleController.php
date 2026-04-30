<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Repositories\Roles\RoleRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    protected $roleRepository;

    public function __construct(RoleRepositoryInterface $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function index(): JsonResponse
    {
        $roles = $this->roleRepository->all();
        return response()->json($roles);
    }

    public function store(RoleRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['guard_name'] = 'api';

            $role = $this->roleRepository->create($validated);
            return response()->json([
                'status' => 'success',
                'message' => 'Role created successfully',
                'data' => $role
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $role = $this->roleRepository->find($id);
        return response()->json($role);
    }

    public function update(RoleRequest $request, $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['guard_name'] = 'api';

            $role = $this->roleRepository->update($id, $validated);
            return response()->json([
                'status' => 'success',
                'message' => 'Role updated successfully',
                'data' => $role
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $this->roleRepository->delete($id);
        return response()->json(['message' => 'Role deleted successfully'], 200);
    }

    public function assignPermissions(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id'
        ]);

        $this->roleRepository->assignPermissions($id, $validated['permission_ids']);
        return response()->json(['message' => 'Permissions assigned successfully']);
    }

    public function removePermissions(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id'
        ]);

        $this->roleRepository->removePermissions($id, $validated['permission_ids']);
        return response()->json(['message' => 'Permissions removed successfully']);
    }

    public function getPermissions($id): JsonResponse
    {
        $permissions = $this->roleRepository->getPermissions($id);
        return response()->json($permissions);
    }

    public function assignRoleToUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        $this->roleRepository->assignRoleToUser($validated['user_id'], $validated['role_id']);
        return response()->json(['message' => 'Role assigned to user successfully']);
    }

    public function removeRoleFromUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        $this->roleRepository->removeRoleFromUser($validated['user_id'], $validated['role_id']);
        return response()->json(['message' => 'Role removed from user successfully']);
    }

    public function getUserRoles($userId): JsonResponse
    {
        $roles = $this->roleRepository->getUserRoles($userId);
        return response()->json($roles);
    }
} 