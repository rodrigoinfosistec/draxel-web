<?php

namespace App\Models;

use App\Models\ProductSupplierReference;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'trade_name',
        'document',
        'state_registration',
        'municipal_registration',
        'email',
        'phone',
        'mobile',
        'zip_code',
        'street',
        'number',
        'complement',
        'district',
        'city',
        'state',
        'notes',
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

    public function supplierReferences(): HasMany
    {
        return $this->hasMany(ProductSupplierReference::class);
    }
}
