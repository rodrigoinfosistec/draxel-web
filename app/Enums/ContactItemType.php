<?php

namespace App\Enums;

enum ContactItemType: string
{
    case CommercialPhone = 'commercial_phone';
    case HomePhone = 'home_phone';
    case Cellphone = 'cellphone';
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case Social = 'social';

    public function label(): string
    {
        return match ($this) {
            self::CommercialPhone => 'Telefone comercial',
            self::HomePhone => 'Telefone residencial',
            self::Cellphone => 'Celular',
            self::Whatsapp => 'WhatsApp',
            self::Email => 'E-mail',
            self::Social => 'Rede social',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values()
            ->all();
    }
}
