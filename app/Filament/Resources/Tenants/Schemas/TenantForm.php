<?php

namespace App\Filament\Resources\Tenants\Schemas;

use App\Models\Module;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->required(),

                TextInput::make('name')
                    ->required(),

                Toggle::make('is_active')
                    ->required(),

                CheckboxList::make('modules')
                    ->label('Módulos vinculados')
                    ->options(
                        Module::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->columns(2)
                    ->dehydrated(false),
            ]);
    }
}
