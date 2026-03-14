<?php

namespace App\Models;

use App\Enums\Weekday;
use App\Traits\BelongsToCompany;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDefaultTime extends Model
{
    use BelongsToTenant, BelongsToCompany;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'weekday',
        'start_time',
        'end_time',
        'break_duration',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => Weekday::class,
        ];
    }

    protected function startTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? substr($value, 0, 5) : null,
        );
    }

    protected function endTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? substr($value, 0, 5) : null,
        );
    }

    protected function breakDuration(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? substr($value, 0, 5) : null,
        );
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
