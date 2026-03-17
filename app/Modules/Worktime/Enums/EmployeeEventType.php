<?php

namespace App\Modules\Worktime\Enums;

enum EmployeeEventType: string
{
    case MedicalCertificate = 'medical_certificate';
    case DayOff = 'day_off';
    case DayAllowance = 'day_allowance';
    case Suspension = 'suspension';
    case Vacation = 'vacation';
    case Leave = 'leave';
    case Compensation = 'compensation';
    case Declaration = 'declaration';
    case PartialAllowance = 'partial_allowance';

    public function label(): string
    {
        return match ($this) {
            self::MedicalCertificate => 'Atestado',
            self::DayOff => 'Folga',
            self::DayAllowance => 'Abono dia',
            self::Suspension => 'Suspensão',
            self::Vacation => 'Férias',
            self::Leave => 'Licença',
            self::Compensation => 'Compensação',
            self::Declaration => 'Declaração',
            self::PartialAllowance => 'Abono parcial',
        };
    }

    public function timeMode(): EmployeeEventTimeMode
    {
        return match ($this) {
            self::Declaration,
            self::PartialAllowance => EmployeeEventTimeMode::Partial,
            default => EmployeeEventTimeMode::Day,
        };
    }

    public function isPartial(): bool
    {
        return $this->timeMode() === EmployeeEventTimeMode::Partial;
    }

    public static function dayOptions(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'time_mode' => $type->timeMode()->value,
            ],
            array_values(array_filter(self::cases(), fn (self $type) => ! $type->isPartial()))
        );
    }

    public static function partialOptions(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'time_mode' => $type->timeMode()->value,
            ],
            array_values(array_filter(self::cases(), fn (self $type) => $type->isPartial()))
        );
    }
}
