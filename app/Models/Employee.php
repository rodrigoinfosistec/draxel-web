<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use BelongsToTenant, BelongsToCompany;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'department_id',
        'position_id',
        'name',
        'cpf',
        'registration',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function employeeTimes(): HasMany
    {
        return $this->hasMany(EmployeeTime::class);
    }
}
