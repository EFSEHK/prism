<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeveloperOrSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['superadmin', 'developer'])) {
            abort(403, 'This area is restricted to developers and super administrators.');
        }

        return $next($request);
    }
}
