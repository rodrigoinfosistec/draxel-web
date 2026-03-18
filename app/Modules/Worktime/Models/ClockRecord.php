<?php

namespace App\Modules\Worktime\Models;

use App\Models\Employee;
use App\Models\User;
use App\Modules\Worktime\Enums\ClockRecordSourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClockRecord extends Model
{
    protected $table = 'clock_records';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'employee_id',
        'tenant_clock_device_id',
        'source_type',
        'source_hash',
        'recorded_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'source_type' => ClockRecordSourceType::class,
        'recorded_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function tenantClockDevice(): BelongsTo
    {
        return $this->belongsTo(TenantClockDevice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
