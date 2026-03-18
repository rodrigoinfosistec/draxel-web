<?php

namespace App\Modules\Worktime\Enums;

enum ClockRecordSourceType: string
{
    case Manual = 'manual';
    case App = 'app';
    case Network = 'network';
    case File = 'file';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::App => 'App',
            self::Network => 'Rede',
            self::File => 'Arquivo',
        };
    }
}
