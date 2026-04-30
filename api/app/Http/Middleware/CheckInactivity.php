<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class CheckInactivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $token = $request->bearerToken();
            if ($token) {
                $accessToken = PersonalAccessToken::findToken($token);
                if ($accessToken) {
                    $lastActivity = $accessToken->last_activity_at;
                    $inactivityTimeout = config('sanctum.inactivity_timeout', 30); // Default 30 minutes
                    
                    // Check if token has been inactive for more than the timeout period
                    if ($lastActivity && Carbon::parse($lastActivity)->diffInMinutes(now()) > $inactivityTimeout) {
                        $accessToken->delete();
                        return response()->json(['message' => 'Session expired due to inactivity'], 401);
                    }

                    // Update last activity time
                    $accessToken->forceFill(['last_activity_at' => now()])->save();
                }
            }
        }

        return $next($request);
    }
}
