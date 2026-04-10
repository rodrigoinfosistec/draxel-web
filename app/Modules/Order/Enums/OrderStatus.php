<?php

namespace App\Modules\Order\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Confirmed => 'Confirmado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isConfirmed(): bool
    {
        return $this === self::Confirmed;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }
}
