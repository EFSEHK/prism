<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthActor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const PROTECTED_ROLES = ['superadmin', 'developer'];

    public function index(): JsonResponse
    {
        abort_unless(AuthActor::canManageUsers(), 403);

        $users = User::query()
            ->orderBy('name')
            ->with(['roles:id,name'])
            ->get(['id', 'name', 'email', 'created_at']);

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        abort_unless(AuthActor::canManageUsers(), 403);

        $user->load(['roles:id,name']);
        $user->setRelation('permissions', $user->getAllPermissions());

        return response()->json($user);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(AuthActor::canManageUsers(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $this->syncRolesForActor($user, $validated['role_ids'] ?? []);

        $user->load(['roles:id,name']);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        abort_unless(AuthActor::canEditUsers(), 403);
        abort_if($user->hasAnyRole(self::PROTECTED_ROLES) && ! AuthActor::user()?->hasRole('superadmin'), 403);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }
        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        if (array_key_exists('role_ids', $validated)) {
            $this->syncRolesForActor($user, $validated['role_ids']);
        }

        $user->load(['roles:id,name']);

        return response()->json($user);
    }

    public function syncRoles(Request $request, User $user): JsonResponse
    {
        abort_unless(AuthActor::canEditUsers(), 403);
        abort_if($user->hasAnyRole(self::PROTECTED_ROLES) && ! AuthActor::user()?->hasRole('superadmin'), 403);

        $validated = $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $this->syncRolesForActor($user, $validated['role_ids']);

        return response()->json([
            'message' => 'Roles updated.',
            'roles' => $user->fresh()->roles()->get(['id', 'name']),
        ]);
    }

    private function syncRolesForActor(User $user, array $roleIds): void
    {
        $roles = Role::query()->whereIn('id', $roleIds)->get();

        if (! AuthActor::user()?->hasRole('superadmin')) {
            $roles = $roles->reject(fn (Role $role) => in_array($role->name, self::PROTECTED_ROLES, true));
        }

        $user->syncRoles($roles);
    }
}
