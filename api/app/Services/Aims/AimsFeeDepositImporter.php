<?php

namespace App\Services\Aims;

use App\Models\FeeDeposit;
use App\Models\FeeVoucher;
use Illuminate\Support\Facades\DB;

class AimsFeeDepositImporter
{
    public function __construct(
        private AimsCsvReader $reader,
        private AimsStudentResolver $students,
    ) {}

    /**
     * @return array{processed: int, succeeded: int, skipped: int, failed: int, errors: list<string>}
     */
    public function import(string $path): array
    {
        $stats = ['processed' => 0, 'succeeded' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];
        $parsed = $this->reader->read($path);

        DB::transaction(function () use ($parsed, &$stats) {
            foreach ($parsed['rows'] as $row) {
                $stats['processed']++;

                $student = $this->students->resolve($row['student_uid'] ?? '', $row['student_cnic'] ?? '');
                if (! $student) {
                    $stats['skipped']++;
                    $stats['errors'][] = 'Unmatched student: '.($row['student_uid'] ?? 'unknown');

                    continue;
                }

                $externalVoucher = (int) ($row['voucher'] ?? 0);
                $amount = (float) ($row['amount'] ?? 0);
                $feeDate = $row['fee_date'] ?? '';

                if ($externalVoucher <= 0 || $amount <= 0 || $feeDate === '') {
                    $stats['skipped']++;

                    continue;
                }

                $exists = FeeDeposit::query()
                    ->where('student_id', $student->id)
                    ->where('external_voucher', $externalVoucher)
                    ->whereDate('fee_date', $feeDate)
                    ->where('amount', $amount)
                    ->exists();

                if ($exists) {
                    $stats['skipped']++;

                    continue;
                }

                $voucher = FeeVoucher::query()
                    ->where('student_id', $student->id)
                    ->where('external_voucher', $externalVoucher)
                    ->first();

                if (! $voucher) {
                    $stats['skipped']++;
                    $stats['errors'][] = ($row['student_uid'] ?? 'unknown').": no voucher {$externalVoucher}";

                    continue;
                }

                FeeDeposit::query()->create([
                    'fee_voucher_id' => $voucher->id,
                    'student_id' => $student->id,
                    'external_voucher' => $externalVoucher,
                    'amount' => $amount,
                    'fee_date' => $feeDate,
                    'imported_at' => now(),
                ]);

                $voucher->update([
                    'total_paid' => max((float) $voucher->total_paid, $amount),
                    'payment_status' => 'paid',
                ]);

                $stats['succeeded']++;
            }
        });

        return $stats;
    }
}
