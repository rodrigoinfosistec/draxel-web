<?php

namespace App\Modules\Worktime\Enums;

enum ClockRecordImportItemStatus: string
{
    case Pending = 'pending';
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Resolved = 'resolved';
    case Launched = 'launched';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Valid => 'Válido',
            self::Invalid => 'Inválido',
            self::Resolved => 'Resolvido',
            self::Launched => 'Lançado',
        };
    }
}
