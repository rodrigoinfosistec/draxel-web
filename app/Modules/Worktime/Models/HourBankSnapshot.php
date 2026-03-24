<?php

namespace App\Modules\Worktime\Models;

use App\Models\User;
use App\Modules\Worktime\Enums\HourBankSnapshotStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HourBankSnapshot extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'name',
        'period_start',
        'period_end',
        'status',
        'notes',
        'created_by',
        'consolidated_by',
        'consolidated_at',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'consolidated_at' => 'datetime',
        'reversed_at' => 'datetime',
        'status' => HourBankSnapshotStatus::class,
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(HourBankSnapshotEmployee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function consolidatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consolidated_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
