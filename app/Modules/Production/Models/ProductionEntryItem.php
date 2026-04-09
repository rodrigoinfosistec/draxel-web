<?php

namespace App\Modules\Production\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionEntryItem extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'production_entry_id',
        'product_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(ProductionEntry::class, 'production_entry_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
