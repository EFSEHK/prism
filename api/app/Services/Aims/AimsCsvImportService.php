<?php

namespace App\Services\Aims;

use App\Models\DataImportLog;
use App\Services\Notifications\NotificationDispatchService;

class AimsCsvImportService
{
    public function __construct(
        private AimsStudentImporter $students,
        private AimsAttendanceImporter $attendance,
        private AimsFeeVoucherImporter $feeVouchers,
        private AimsFeeDepositImporter $feeDeposits,
        private AimsTestResultsImporter $testResults,
        private AimsExamResultsImporter $examResults,
    ) {}

    /**
     * @return array{processed: int, succeeded: int, skipped: int, failed: int, errors: list<string>}
     */
    public function import(string $dataType, string $path, int $userId, ?NotificationDispatchService $dispatchService = null): array
    {
        return match ($dataType) {
            'students' => $this->students->import($path),
            'attendance' => $this->attendance->import($path, $userId),
            'fee_vouchers' => $this->feeVouchers->import($path, $userId, $dispatchService),
            'fee_deposits' => $this->feeDeposits->import($path),
            'test_results' => $this->testResults->import($path, $userId),
            'exam_results' => $this->examResults->import($path, $userId),
            default => throw new \InvalidArgumentException("Unknown import type: {$dataType}"),
        };
    }

    public function log(int $userId, string $dataType, string $filename, array $stats): DataImportLog
    {
        return DataImportLog::query()->create([
            'user_id' => $userId,
            'source' => 'aims',
            'data_type' => $dataType,
            'filename' => $filename,
            'stats' => $stats,
            'created_at' => now(),
        ]);
    }
}
