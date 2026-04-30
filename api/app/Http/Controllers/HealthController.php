<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Spatie\Health\Enums\Status;
use Spatie\Health\Health as HealthManager;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResults;

class HealthController extends Controller
{
    public function simple(HealthManager $health): JsonResponse
    {
        $storedResults = $this->runChecks($health);

        return response()
            ->json([
                'healthy' => $storedResults->allChecksOk(),
                'finishedAt' => $storedResults->finishedAt->format(DATE_ATOM),
            ], $storedResults->allChecksOk() ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    }

    public function json(HealthManager $health): Response
    {
        $storedResults = $this->runChecks($health);

        $statusCode = $storedResults->containsFailingCheck()
            ? (int) config('health.json_results_failure_status', Response::HTTP_SERVICE_UNAVAILABLE)
            : Response::HTTP_OK;

        return response($storedResults->toJson(), $statusCode)
            ->header('Content-Type', 'application/json')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    }

    public function results(HealthManager $health): View
    {
        $storedResults = $this->runChecks($health);

        return view('health::list', [
            'lastRanAt' => new Carbon($storedResults->finishedAt),
            'checkResults' => $storedResults,
            'assets' => $health->assets(),
            'theme' => config('health.theme'),
        ]);
    }

    protected function runChecks(HealthManager $health): StoredCheckResults
    {
        $results = $health->registeredChecks()->map(
            fn (Check $check) => $check->shouldRun()
                ? $this->runCheck($check)
                : (new Result(Status::skipped()))->check($check)->endedAt(now())
        );

        $health->resultStores()->each(
            fn (ResultStore $store) => $store->save($results)
        );

        return app(ResultStore::class)->latestResults() ?? new StoredCheckResults(now());
    }

    protected function runCheck(Check $check): Result
    {
        try {
            $result = $check->run();
        } catch (Exception $exception) {
            report($exception);

            $result = $check->markAsCrashed();
        }

        return $result
            ->check($check)
            ->endedAt(now());
    }
}
