<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeDeposit extends Model
{
    protected $fillable = [
        'fee_voucher_id',
        'student_id',
        'external_voucher',
        'amount',
        'fee_date',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee_date' => 'date',
            'imported_at' => 'datetime',
        ];
    }

    public function feeVoucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
