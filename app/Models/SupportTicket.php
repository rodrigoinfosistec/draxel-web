<?php

namespace App\Models;

use App\Enums\SupportTicketStatus;
use App\Traits\BelongsToCompany;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use BelongsToTenant, BelongsToCompany;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'created_by',
        'assigned_to',
        'code',
        'subject',
        'description',
        'status',
        'is_open',
        'last_interaction_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupportTicketStatus::class,
            'is_open' => 'boolean',
            'last_interaction_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }
}
