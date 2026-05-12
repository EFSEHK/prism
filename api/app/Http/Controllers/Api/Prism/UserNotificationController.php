<?php

namespace App\Http\Controllers\Api\Prism;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function index(Request $request)
    {
        $q = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at');

        return response()->json($q->paginate(min((int) $request->query('per_page', 25), 50)));
    }

    public function markRead(Request $request, UserNotification $userNotification)
    {
        abort_unless($userNotification->user_id === $request->user()->id, 403);

        $userNotification->update(['read_at' => now()]);

        return response()->json($userNotification);
    }
}
