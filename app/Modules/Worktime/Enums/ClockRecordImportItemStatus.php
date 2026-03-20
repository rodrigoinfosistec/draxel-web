<?php

namespace App\Modules\Worktime\Enums;

enum ClockRecordImportItemStatus: string
{
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Resolved = 'resolved';
    case Ignored = 'ignored';
    case Launched = 'launched';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Válido',
            self::Invalid => 'Divergente',
            self::Resolved => 'Resolvido',
            self::Ignored => 'Desconsiderado',
            self::Launched => 'Lançado',
        };
    }
}
