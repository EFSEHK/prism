<?php

namespace App\Http\Controllers\Api\Prism;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string|max:512',
            'platform' => 'nullable|string|max:16',
        ]);

        DeviceToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'token' => $data['token'],
            ],
            [
                'platform' => $data['platform'] ?? 'unknown',
                'revoked_at' => null,
            ]
        );

        return response()->json(['message' => 'Token registered']);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['token' => 'required|string|max:512']);

        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $data['token'])
            ->update(['revoked_at' => now()]);

        return response()->json(['message' => 'Token revoked']);
    }
}
