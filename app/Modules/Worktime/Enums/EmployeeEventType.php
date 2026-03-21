<?php

namespace App\Modules\Worktime\Enums;

enum EmployeeEventType: string
{
    case MedicalCertificate = 'medical_certificate';
    case DayOff = 'day_off';
    case Suspension = 'suspension';
    case Vacation = 'vacation';
    case Leave = 'leave';
    case Compensation = 'compensation';
    case Declaration = 'declaration';

    public function label(): string
    {
        return match ($this) {
            self::MedicalCertificate => 'Atestado',
            self::DayOff => 'Folga',
            self::Suspension => 'Suspensão',
            self::Vacation => 'Férias',
            self::Leave => 'Licença',
            self::Compensation => 'Compensação',
            self::Declaration => 'Declaração',
        };
    }

    public static function formOptions(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases(),
        );
    }

    public function movesBankHourImmediately(): bool
    {
        return $this === self::Compensation;
    }
}
