<?php

namespace App\Modules\Worktime\Models;

use App\Models\Employee;
use App\Models\Tenant;
use App\Modules\Worktime\Models\BankHourEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankHourAccount extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'employee_id',
        'current_balance_minutes',
    ];

    protected function casts(): array
    {
        return [
            'current_balance_minutes' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(BankHourEntry::class);
    }
}
