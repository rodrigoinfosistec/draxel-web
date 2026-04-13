<?php

namespace App\Modules\PurchaseReceipt\Models;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\PurchaseReceipt\Enums\PurchaseReceiptStatus;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReceipt extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'supplier_id',
        'warehouse_id',
        'received_by',
        'number',
        'invoice_number',
        'issue_date',
        'receipt_date',
        'status',
        'notes',
        'total_amount',
        'received_at',
        'canceled_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'receipt_date' => 'date',
        'received_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReceiptItem::class);
    }

    public function scopeForCurrentContext(Builder $query): Builder
    {
        $tenantId = TenantContext::id();
        $companyId = CompanyContext::id();

        return $query
            ->when($tenantId, fn (Builder $builder) => $builder->where('tenant_id', $tenantId))
            ->when($companyId, fn (Builder $builder) => $builder->where('company_id', $companyId));
    }

    public function isDraft(): bool
    {
        return $this->status === PurchaseReceiptStatus::Draft->value;
    }

    public function isReceived(): bool
    {
        return $this->status === PurchaseReceiptStatus::Received->value;
    }

    public function isCanceled(): bool
    {
        return $this->status === PurchaseReceiptStatus::Canceled->value;
    }
}
