<?php

namespace App\Modules\Worktime\Models;

use App\Models\Employee;
use App\Modules\Worktime\Enums\ClockRecordImportItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClockRecordImportItem extends Model
{
    protected $table = 'clock_record_import_items';

    protected $fillable = [
        'clock_record_import_id',
        'line_number',
        'raw_line',
        'employee_code',
        'employee_id',
        'recorded_at',
        'status',
        'record_hash',
        'divergence_reason',
        'payload',
        'launched_clock_record_id',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'status' => ClockRecordImportItemStatus::class,
        'payload' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(ClockRecordImport::class, 'clock_record_import_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function launchedClockRecord(): BelongsTo
    {
        return $this->belongsTo(ClockRecord::class, 'launched_clock_record_id');
    }
}
