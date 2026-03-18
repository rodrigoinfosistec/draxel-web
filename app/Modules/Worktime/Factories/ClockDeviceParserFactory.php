<?php

namespace App\Modules\Worktime\Factories;

use App\Modules\Worktime\Contracts\ClockDeviceParserInterface;
use App\Modules\Worktime\Models\TenantClockDevice;
use InvalidArgumentException;

class ClockDeviceParserFactory
{
    public function make(TenantClockDevice $tenantClockDevice): ClockDeviceParserInterface
    {
        $slug = $tenantClockDevice->clockDevice?->slug;

        $parserClass = config("worktime.device_parsers.{$slug}");

        if (! $parserClass || ! class_exists($parserClass)) {
            throw new InvalidArgumentException('Parser do device não configurado.');
        }

        $parser = app($parserClass);

        if (! $parser instanceof ClockDeviceParserInterface) {
            throw new InvalidArgumentException('Parser inválido para o device informado.');
        }

        return $parser;
    }
}
