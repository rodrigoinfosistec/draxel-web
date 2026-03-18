<?php

namespace App\Modules\Worktime\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ClockDevice extends Model
{
    protected $table = 'clock_devices';

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $clockDevice) {
            if (blank($clockDevice->slug) && filled($clockDevice->name)) {
                $clockDevice->slug = Str::slug($clockDevice->name);
            }
        });

        static::updating(function (self $clockDevice) {
            if (blank($clockDevice->slug) && filled($clockDevice->name)) {
                $clockDevice->slug = Str::slug($clockDevice->name);
            }
        });
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(
            Tenant::class,
            'tenant_clock_devices'
        )->withTimestamps();
    }
}
