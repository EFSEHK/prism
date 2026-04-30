<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LogAllRequests
{
    /**
     * Routes you want to skip logging.
     */
    protected $exceptRoutes = [
        'login',
        'register',
        'password/reset',
        'password/email',
        'password/confirm',
        'sanctum/csrf-cookie',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if current request should be skipped
        foreach ($this->exceptRoutes as $route) {
            if ($request->is($route)) {
                return $next($request); // Skip logging
            }
        }

        // Prepare to capture queries
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ];
        });

        // Let the request proceed (this triggers DB queries)
        $response = $next($request);

        activity('Frontend Request')
            ->causedBy(Auth::check() ? Auth::user() : null)
            ->withProperties([
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'input' => $request->except(['password', 'token']),
                'status' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'query' => $queries,
                // 'headers' => $request->headers->all(), // Uncomment if you want full headers (very detailed)
            ])
            ->log('Request made');

        return $response;
    }
}
