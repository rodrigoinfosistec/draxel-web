<?php

namespace App\Enums;

enum Weekday: string
{
    case SUNDAY = 'sunday';
    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';

    public function label(): string
    {
        return match ($this) {
            self::SUNDAY => 'Domingo',
            self::MONDAY => 'Segunda-feira',
            self::TUESDAY => 'Terça-feira',
            self::WEDNESDAY => 'Quarta-feira',
            self::THURSDAY => 'Quinta-feira',
            self::FRIDAY => 'Sexta-feira',
            self::SATURDAY => 'Sábado',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::SUNDAY => 0,
            self::MONDAY => 1,
            self::TUESDAY => 2,
            self::WEDNESDAY => 3,
            self::THURSDAY => 4,
            self::FRIDAY => 5,
            self::SATURDAY => 6,
        };
    }

    public static function values(): array
    {
        return array_map(
            fn (self $day) => $day->value,
            self::cases(),
        );
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $day) {
            $options[$day->value] = $day->label();
        }

        return $options;
    }
}
