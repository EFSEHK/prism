<?php

namespace App\Http\Controllers;

use App\Services\SystemStatsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DashboardController extends Controller
{
    public function index(SystemStatsService $stats): View
    {
        return view('dashboard', [
            'cards' => $stats->getDashboardCards(),
            'packages' => [
                [
                    'label' => 'Logs',
                    'description' => 'Inspect Laravel log files and request history.',
                    'url' => url('/logs'),
                ],
                [
                    'label' => 'Telescope',
                    'description' => 'Review requests, queries, jobs, and exceptions.',
                    'url' => url('/telescope'),
                ],
                [
                    'label' => 'Health',
                    'description' => 'Open the Spatie Health overview for live system checks.',
                    'url' => route('dashboard.health'),
                ],
            ],
        ]);
    }

    public function activeConnections(SystemStatsService $stats): JsonResponse
    {
        $card = $stats->activeConnectionsCard();
        $count = $card['status'] === 'failed'
            ? null
            : $stats->activeConnectionsCount();

        return response()->json([
            'label' => $card['title'],
            'value' => $card['value'],
            'detail' => $card['detail'],
            'connections' => $count,
            'status' => $card['status'],
        ], $card['status'] === 'failed' ? Response::HTTP_SERVICE_UNAVAILABLE : Response::HTTP_OK);
    }
}
