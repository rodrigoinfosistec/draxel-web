<?php

namespace App\Modules\Worktime\Models;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HourBankSnapshotEmployeeDay extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'hour_bank_snapshot_id',
        'hour_bank_snapshot_employee_id',
        'employee_id',
        'work_date',
        'weekday',
        'weekday_label',
        'expected_start_time',
        'expected_end_time',
        'expected_break_duration',
        'records',
        'justified_minutes',
        'late_minutes',
        'extra_minutes',
        'absence_minutes',
        'suspension_minutes',
        'dsr_worked_minutes',
        'balance_minutes',
        'has_divergence',
        'divergence_reason',
        'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'records' => 'array',
        'has_divergence' => 'boolean',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(HourBankSnapshot::class, 'hour_bank_snapshot_id');
    }

    public function snapshotEmployee(): BelongsTo
    {
        return $this->belongsTo(HourBankSnapshotEmployee::class, 'hour_bank_snapshot_employee_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
