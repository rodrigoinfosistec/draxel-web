<?php

namespace App\Modules\Production\Enums;

enum ProductionEntryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Posted => 'Lançado',
            self::Cancelled => 'Cancelado',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
