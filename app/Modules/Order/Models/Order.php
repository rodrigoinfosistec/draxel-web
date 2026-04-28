<?php

namespace App\Modules\Order\Models;

use App\Models\Client;
use App\Models\User;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\OrderType;
use App\Traits\BelongsToCompany;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToTenant;
    use BelongsToCompany;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'warehouse_id',
        'client_id',
        'number',
        'type',
        'status',
        'destination_name',
        'notes',
        'issued_at',
        'confirmed_at',
        'cancelled_at',
        'created_by',
        'confirmed_by',
        'cancelled_by',
    ];

    protected $casts = [
        'type' => OrderType::class,
        'status' => OrderStatus::class,
        'issued_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(
                filled($filters['search'] ?? null),
                fn (Builder $builder) => $builder->where(function (Builder $sub) use ($filters) {
                    $search = trim((string) $filters['search']);

                    $sub->where('number', 'ilike', "%{$search}%")
                        ->orWhere('destination_name', 'ilike', "%{$search}%")
                        ->orWhere('notes', 'ilike', "%{$search}%")
                        ->orWhereHas('client', function (Builder $clientQuery) use ($search) {
                            $clientQuery
                                ->where('name', 'ilike', "%{$search}%")
                                ->orWhere('document', 'ilike', "%{$search}%");
                        });
                })
            )
            ->when(
                filled($filters['status'] ?? null),
                fn (Builder $builder) => $builder->where('status', $filters['status'])
            )
            ->when(
                filled($filters['warehouse_id'] ?? null),
                fn (Builder $builder) => $builder->where('warehouse_id', (int) $filters['warehouse_id'])
            )
            ->when(
                filled($filters['client_id'] ?? null),
                fn (Builder $builder) => $builder->where('client_id', (int) $filters['client_id'])
            )
            ->when(
                filled($filters['type'] ?? null),
                fn (Builder $builder) => $builder->where('type', $filters['type'])
            )
            ->when(
                filled($filters['start_date'] ?? null),
                fn (Builder $builder) => $builder->whereDate('issued_at', '>=', $filters['start_date'])
            )
            ->when(
                filled($filters['end_date'] ?? null),
                fn (Builder $builder) => $builder->whereDate('issued_at', '<=', $filters['end_date'])
            );
    }

    public function isDraft(): bool
    {
        return $this->status?->isDraft() ?? false;
    }

    public function isConfirmed(): bool
    {
        return $this->status?->isConfirmed() ?? false;
    }

    public function isCancelled(): bool
    {
        return $this->status?->isCancelled() ?? false;
    }
}
