<?php

namespace App\Services\Aims;

use App\Models\FeeVoucher;
use App\Services\Notifications\NotificationDispatchService;
use App\Support\NotificationFeatureKeys;
use Illuminate\Support\Facades\DB;

class AimsFeeVoucherImporter
{
    public function __construct(
        private AimsCsvReader $reader,
        private AimsStudentResolver $students,
    ) {}

    /**
     * @return array{processed: int, succeeded: int, skipped: int, failed: int, errors: list<string>}
     */
    public function import(string $path, int $userId, ?NotificationDispatchService $dispatchService = null): array
    {
        $stats = ['processed' => 0, 'succeeded' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];
        $parsed = $this->reader->read($path);

        DB::transaction(function () use ($parsed, $userId, $dispatchService, &$stats) {
            foreach ($parsed['rows'] as $row) {
                $stats['processed']++;

                $student = $this->students->resolve($row['student_uid'] ?? '', $row['student_cnic'] ?? '');
                if (! $student) {
                    $stats['skipped']++;
                    $stats['errors'][] = 'Unmatched student: '.($row['student_uid'] ?? 'unknown');

                    continue;
                }

                $externalVoucher = (int) ($row['voucher'] ?? 0);
                if ($externalVoucher <= 0) {
                    $stats['skipped']++;

                    continue;
                }

                $voucherMonth = $row['voucher_month'] ?? null;
                $title = $voucherMonth
                    ? 'Fee — '.date('M Y', strtotime($voucherMonth))
                    : 'Fee voucher #'.$externalVoucher;

                $paymentStatus = (int) ($row['voucher_status'] ?? 1) === 2 ? 'paid' : 'unpaid';

                $existing = FeeVoucher::query()
                    ->where('student_id', $student->id)
                    ->where('external_voucher', $externalVoucher)
                    ->first();

                $voucher = FeeVoucher::query()->updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'external_voucher' => $externalVoucher,
                    ],
                    [
                        'title' => $title,
                        'voucher_month' => $voucherMonth ?: null,
                        'due_date' => ($row['due_date'] ?? '') !== '' ? $row['due_date'] : null,
                        'voucher_type' => $row['voucher_type'] ?? null,
                        'voucher_no' => $row['voucher_no'] ?? null,
                        'total_due' => (float) ($row['total_due'] ?? 0),
                        'total_paid' => (float) ($row['total_paid'] ?? 0),
                        'payment_status' => $paymentStatus,
                        'submission_status' => 'pending',
                        'updated_by_user_id' => $userId,
                    ]
                );

                $stats['succeeded']++;

                if (! $existing && $dispatchService) {
                    $dispatchService->create(
                        NotificationFeatureKeys::FEE_VOUCHER_AVAILABLE,
                        'FeeVoucher',
                        $voucher->id,
                        'student',
                        ['student_ids' => [$student->id]],
                        [
                            'title' => 'Fee voucher',
                            'body' => $voucher->title,
                            'data' => ['type' => 'fee_voucher', 'fee_voucher_id' => $voucher->id],
                        ],
                        createdByUserId: $userId,
                    );
                }
            }
        });

        return $stats;
    }
}
