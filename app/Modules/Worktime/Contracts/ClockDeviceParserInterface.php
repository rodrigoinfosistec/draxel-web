<?php

namespace App\Modules\Worktime\Contracts;

interface ClockDeviceParserInterface
{
    public function validate(string $content): void;

    public function parse(string $content): array;
}
