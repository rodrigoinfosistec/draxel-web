<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use BelongsToTenant, BelongsToCompany;

    protected $table = 'audit_logs';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'user_id',
        'event',
        'subject_type',
        'subject_id',
        'route',
        'method',
        'ip',
        'user_agent',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFromTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeFromCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeFromUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    public function scopeSubject(Builder $query, string $subjectType, string|int $subjectId): Builder
    {
        return $query
            ->where('subject_type', $subjectType)
            ->where('subject_id', (string) $subjectId);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->latest();
    }
}
