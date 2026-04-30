<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionRequest;
use App\Repositories\Permissions\PermissionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    protected $permissionRepository;

    public function __construct(PermissionRepositoryInterface $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    public function index(): JsonResponse
    {
        $permissions = $this->permissionRepository->all();
        return response()->json($permissions);
    }

    public function store(PermissionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['guard_name'] = 'api';
            
            $permission = $this->permissionRepository->create($validated);
            return response()->json([
                'status' => 'success',
                'message' => 'Permission created successfully',
                'data' => $permission
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $permission = $this->permissionRepository->find($id);
        return response()->json($permission);
    }

    public function update(PermissionRequest $request, $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['guard_name'] = 'api';
            
            $permission = $this->permissionRepository->update($id, $validated);
            return response()->json([
                'status' => 'success',
                'message' => 'Permission updated successfully',
                'data' => $permission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $this->permissionRepository->delete($id);
        return response()->json(['message' => 'Permission deleted successfully'], 200);
    }

    public function assignPermissionToUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission_id' => 'required|exists:permissions,id'
        ]);

        $this->permissionRepository->assignPermissionToUser(
            $validated['user_id'],
            $validated['permission_id']
        );
        return response()->json(['message' => 'Permission assigned to user successfully']);
    }

    public function removePermissionFromUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission_id' => 'required|exists:permissions,id'
        ]);

        $this->permissionRepository->removePermissionFromUser(
            $validated['user_id'],
            $validated['permission_id']
        );
        return response()->json(['message' => 'Permission removed from user successfully']);
    }

    public function getUserPermissions($userId): JsonResponse
    {
        $permissions = $this->permissionRepository->getUserPermissions($userId);
        return response()->json($permissions);
    }
} 