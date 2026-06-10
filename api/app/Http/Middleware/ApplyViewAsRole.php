<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class ApplyViewAsRole
{
    private const PRIVILEGED = ['superadmin'];

    private const BLOCKED_TARGETS = ['superadmin', 'developer'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($request->header('X-View-As-User')) {
            return $next($request);
        }

        $viewAs = $request->header('X-View-As-Role');
        if (! $viewAs || ! $user->hasAnyRole(self::PRIVILEGED)) {
            return $next($request);
        }

        if (in_array($viewAs, self::BLOCKED_TARGETS, true)) {
            return $next($request);
        }

        try {
            $role = Role::findByName($viewAs, 'web');
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist) {
            return $next($request);
        }

        $user->setRelation('roles', collect([$role]));
        $user->unsetRelation('permissions');

        return $next($request);
    }
}
