<?php

namespace App\Modules\Worktime\Models;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HourBankSnapshotEmployee extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'hour_bank_snapshot_id',
        'employee_id',
        'employee_name',
        'employee_registration',
        'justified_minutes',
        'late_minutes',
        'extra_minutes',
        'absence_minutes',
        'suspension_minutes',
        'balance_minutes',
        'has_divergence',
        'divergence_summary',
        'captured_at',
        'captured_by',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'has_divergence' => 'boolean',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(HourBankSnapshot::class, 'hour_bank_snapshot_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }

    public function days(): HasMany
    {
        return $this->hasMany(HourBankSnapshotEmployeeDay::class);
    }
}
