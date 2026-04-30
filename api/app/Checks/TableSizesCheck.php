<?php

namespace App\Checks;

use App\Services\SystemStatsService;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class TableSizesCheck extends Check
{
    public function __construct(protected SystemStatsService $stats)
    {
    }

    public function run(): Result
    {
        return $this->buildResult($this->stats->tableSizesCard());
    }

    protected function buildResult(array $card): Result
    {
        $result = Result::make()->shortSummary($card['detail']);

        return match ($card['status']) {
            'failed' => $result->failed($card['value']),
            'warning' => $result->warning($card['value']),
            default => $result->ok($card['value']),
        };
    }
}
