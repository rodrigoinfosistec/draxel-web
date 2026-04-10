<?php

namespace App\Modules\Order\Enums;

enum OrderType: string
{
    case Sale = 'sale';
    case Transfer = 'transfer';
    case InternalUse = 'internal_use';
    case Loss = 'loss';
    case Bonus = 'bonus';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Venda',
            self::Transfer => 'Transferência',
            self::InternalUse => 'Uso interno',
            self::Loss => 'Perda',
            self::Bonus => 'Bonificação',
        };
    }

    public static function formOptions(): array
    {
        return collect(self::cases())
            ->map(fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values()
            ->all();
    }
}
