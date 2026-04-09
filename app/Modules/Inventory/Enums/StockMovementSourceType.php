<?php

namespace App\Modules\Inventory\Enums;

enum StockMovementSourceType: string
{
    case Manual = 'manual';
    case Purchase = 'purchase';
    case Production = 'production';
    case Order = 'order';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Purchase => 'Compra',
            self::Production => 'Produção',
            self::Order => 'Pedido',
        };
    }
}
