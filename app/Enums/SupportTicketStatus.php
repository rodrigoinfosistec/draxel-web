<?php

namespace App\Enums;

enum SupportTicketStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case WAITING_CUSTOMER = 'waiting_customer';
    case WAITING_SUPPORT = 'waiting_support';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Aberto',
            self::IN_PROGRESS => 'Em andamento',
            self::WAITING_CUSTOMER => 'Aguardando cliente',
            self::WAITING_SUPPORT => 'Aguardando suporte',
            self::RESOLVED => 'Resolvido',
            self::CLOSED => 'Fechado',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::RESOLVED, self::CLOSED], true);
    }
}
