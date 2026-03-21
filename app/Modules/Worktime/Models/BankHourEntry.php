<?php

namespace App\Modules\Worktime\Models;

use App\Models\Employee;
use App\Models\Tenant;
use App\Modules\Worktime\Enums\BankHourEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankHourEntry extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'employee_id',
        'bank_hour_account_id',
        'entry_type',
        'minutes',
        'occurred_on',
        'description',
        'metadata',
        'source_type',
        'source_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_type' => BankHourEntryType::class,
            'minutes' => 'integer',
            'occurred_on' => 'date',
            'metadata' => 'array',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(BankHourAccount::class, 'bank_hour_account_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
