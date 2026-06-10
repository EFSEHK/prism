<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class ViewAsController extends Controller
{
    private const PRIVILEGED = ['superadmin'];

    private const BLOCKED_TARGETS = ['superadmin', 'developer'];

    private const LABELS = [
        'admin' => 'Admin',
        'principal' => 'Principal',
        'vice_principal' => 'Vice Principal',
        'section_head' => 'Section Head',
        'class_incharge' => 'Class Incharge',
        'teacher' => 'Teacher',
        'parent' => 'Parent',
        'student' => 'Student',
        'computer_operator' => 'Computer Operator',
        'accountant' => 'Accountant',
    ];

    public function roles(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(self::PRIVILEGED), 403);

        $roles = Role::query()
            ->whereNotIn('name', self::BLOCKED_TARGETS)
            ->orderBy('name')
            ->with('permissions:id,name')
            ->get(['id', 'name']);

        return response()->json(
            $roles->map(fn (Role $role) => [
                'name' => $role->name,
                'label' => self::LABELS[$role->name] ?? ucwords(str_replace('_', ' ', $role->name)),
                'permissions' => $role->permissions->pluck('name')->values(),
            ])->values()
        );
    }
}
