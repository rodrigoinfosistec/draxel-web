<?php

namespace App\Modules\PurchaseReceipt\Enums;

enum PurchaseReceiptStatus: string
{
    case Draft = 'draft';
    case Received = 'received';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Em digitação',
            self::Received => 'Recebido',
            self::Canceled => 'Cancelado',
        };
    }
}
