<?php

namespace App\Modules\Worktime\Models;

use App\Models\Employee;
use App\Models\User;
use App\Modules\Worktime\Enums\EmployeeEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEvent extends Model
{
    protected $table = 'employee_events';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'employee_id',
        'event_type',
        'starts_at',
        'ends_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'event_type' => EmployeeEventType::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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
