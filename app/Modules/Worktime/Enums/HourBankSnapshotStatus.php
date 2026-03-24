<?php

namespace App\Modules\Worktime\Enums;

enum HourBankSnapshotStatus: string
{
    case DRAFT = 'draft';
    case CONSOLIDATED = 'consolidated';
    case REVERSED = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Em revisão',
            self::CONSOLIDATED => 'Consolidado',
            self::REVERSED => 'Revertido',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    public function isConsolidated(): bool
    {
        return $this === self::CONSOLIDATED;
    }
}
