<?php

namespace App\Modules\Worktime\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantClockDevice extends Model
{
    protected $table = 'tenant_clock_devices';

    protected $fillable = [
        'tenant_id',
        'clock_device_id',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function clockDevice(): BelongsTo
    {
        return $this->belongsTo(ClockDevice::class);
    }
}
