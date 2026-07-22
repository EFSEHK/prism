<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeVoucher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'external_voucher',
        'voucher_month',
        'due_date',
        'voucher_type',
        'voucher_no',
        'total_due',
        'total_paid',
        'payment_status',
        'title',
        'file_path',
        'submission_status',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'voucher_month' => 'date',
            'due_date' => 'date',
            'total_due' => 'decimal:2',
            'total_paid' => 'decimal:2',
        ];
    }

    public function deposits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FeeDeposit::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
