<?php

namespace App\Modules\Worktime\Models;

use App\Models\User;
use App\Modules\Worktime\Enums\ClockRecordImportStatus;
use App\Modules\Worktime\Enums\ClockRecordSourceType;
use App\Modules\Worktime\Models\ClockRecordImportItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClockRecordImport extends Model
{
    protected $table = 'clock_record_imports';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'tenant_clock_device_id',
        'source_type',
        'status',
        'original_filename',
        'stored_path',
        'file_hash',
        'total_items',
        'valid_items',
        'invalid_items',
        'notes',
        'imported_by',
        'processed_at',
        'launched_at',
    ];

    protected $casts = [
        'source_type' => ClockRecordSourceType::class,
        'status' => ClockRecordImportStatus::class,
        'processed_at' => 'datetime',
        'launched_at' => 'datetime',
    ];

    public function tenantClockDevice(): BelongsTo
    {
        return $this->belongsTo(TenantClockDevice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClockRecordImportItem::class);
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
