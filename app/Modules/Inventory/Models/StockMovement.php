<?php

namespace App\Modules\Inventory\Models;

use App\Models\Product;
use App\Models\User;
use App\Modules\Inventory\Enums\StockMovementSourceType;
use App\Modules\Inventory\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'warehouse_id',
        'product_id',
        'type',
        'source_type',
        'source_id',
        'quantity',
        'unit_cost',
        'reference',
        'notes',
        'moved_at',
        'user_id',
    ];

    protected $casts = [
        'type' => StockMovementType::class,
        'source_type' => StockMovementSourceType::class,
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'moved_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
