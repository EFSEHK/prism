<?php

namespace App\Http\Controllers;

use App\Services\HealthMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HealthMetricsController extends Controller
{
    public function __invoke(Request $request, HealthMetricsService $metrics): JsonResponse
    {
        $range = (string) $request->query('range', '1m');

        try {
            return response()
                ->json($metrics->sampleAndBuild($range))
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        } catch (Throwable $exception) {
            report($exception);

            return response()
                ->json([
                    'message' => 'Unable to load active connection metrics right now.',
                ], Response::HTTP_SERVICE_UNAVAILABLE)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }
    }
}
