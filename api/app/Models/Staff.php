<?php

namespace App\Models;

use App\Casts\AppDate;
use App\Enums\StaffClassGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'department_id',
        'name',
        'designation',
        'gender',
        'contact_no',
        'date_of_joining',
        'qualification',
        'classes',
        'subject',
        'cnic',
    ];

    protected function casts(): array
    {
        return [
            'date_of_joining' => AppDate::class,
            'classes' => StaffClassGroup::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
