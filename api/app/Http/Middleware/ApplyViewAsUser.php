<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyViewAsUser
{
    private const BLOCKED_TARGETS = ['superadmin', 'developer'];

    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();
        if (! $actor) {
            return $next($request);
        }

        $viewAsUserId = $request->header('X-View-As-User');
        if (! $viewAsUserId) {
            return $next($request);
        }

        abort_unless($actor->hasRole('superadmin'), 403);

        $targetUser = User::query()
            ->with(['roles:id,name'])
            ->find($viewAsUserId);

        abort_unless($targetUser, 404);
        abort_if($targetUser->hasAnyRole(self::BLOCKED_TARGETS), 403);

        $targetUser->setRelation('permissions', $targetUser->getAllPermissions());

        app()->instance('auth.actor', $actor);
        $request->setUserResolver(fn () => $targetUser);

        return $next($request);
    }
}
