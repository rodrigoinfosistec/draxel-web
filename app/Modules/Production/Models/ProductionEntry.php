<?php

namespace App\Modules\Production\Models;

use App\Models\User;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Production\Enums\ProductionEntryStatus;
use App\Modules\Production\Models\ProductionEntryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionEntry extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'warehouse_id',
        'number',
        'entry_date',
        'status',
        'notes',
        'created_by',
        'posted_by',
        'posted_at',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'status' => ProductionEntryStatus::class,
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionEntryItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isDraft(): bool
    {
        return $this->status === ProductionEntryStatus::Draft;
    }

    public function isPosted(): bool
    {
        return $this->status === ProductionEntryStatus::Posted;
    }

    public function isCancelled(): bool
    {
        return $this->status === ProductionEntryStatus::Cancelled;
    }
}
