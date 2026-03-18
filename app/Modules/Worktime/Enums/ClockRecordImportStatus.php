<?php

namespace App\Modules\Worktime\Enums;

enum ClockRecordImportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case AwaitingReview = 'awaiting_review';
    case ReadyToLaunch = 'ready_to_launch';
    case Launched = 'launched';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Processing => 'Processando',
            self::AwaitingReview => 'Aguardando revisão',
            self::ReadyToLaunch => 'Pronto para lançar',
            self::Launched => 'Lançado',
            self::Failed => 'Falhou',
        };
    }
}
