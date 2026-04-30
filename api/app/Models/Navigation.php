<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Permission;

class Navigation extends Model
{
    use SoftDeletes;
    use LogsActivity;
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('Navigation')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'parent_id',
        'name',
        'path',
        'component',
        'icon',
        'permission_id',
        'order',
        'is_active',
        'is_hidden'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_hidden' => 'boolean',
        'order' => 'integer'
    ];

    public function parent()
    {
        return $this->belongsTo(Navigation::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Navigation::class, 'parent_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

}
