<?php
namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\DB;

class LogAuthEvents
{
    public function handle($event)
    {
        try {
            $eventName = class_basename($event); // e.g. "Login", "Logout", "Failed"
            $queries = [];

            DB::listen(function ($query) use (&$queries) {
                $queries[] = [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ];
            });

            activity('Auth')
                ->causedBy($event->user ?? null)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'email' => $event->user->email ?? request('email'),
                    'query' => $queries,
                ])
                ->log("User {$eventName}");
        } catch (\Exception $e) {
            Log::error('Failed to log auth event: ' . $e->getMessage());
        }
    }
}
