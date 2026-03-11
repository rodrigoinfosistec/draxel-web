<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompaniesRelationManager extends RelationManager
{
    protected static string $relationship = 'companies';

    protected static ?string $title = 'Empresas';
    protected static ?string $label = 'Empresa';
    protected static ?string $pluralLabel = 'Empresas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('alias')
                    ->maxLength(255),

                TextInput::make('cnpj')
                    ->label('CNPJ')
                    ->mask('99.999.999/9999-99')
                    ->placeholder('00.000.000/0000-00')
                    ->required()
                    ->stripCharacters(['.', '/', '-'])
                    ->rule('digits:14'),

                ColorPicker::make('color')
                    ->label('Cor do tema')
                    ->required()
                    ->default('#7C3AED'),

                Toggle::make('is_active')
                    ->label('Empresa ativa')
                    ->visible(fn ($operation) => $operation === 'edit'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withoutTenant())
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('alias')
                    ->searchable(),

                TextColumn::make('cnpj')
                    ->formatStateUsing(fn ($state) =>
                        substr($state, 0, 2) . '.' .
                        substr($state, 2, 3) . '.' .
                        substr($state, 5, 3) . '/' .
                        substr($state, 8, 4) . '-' .
                        substr($state, 12, 2)
                    )
                    ->searchable(),

                TextColumn::make('color')
                    ->label('Cor')
                    ->formatStateUsing(fn ($state) => strtoupper($state))
                    ->extraAttributes(fn ($record) => [
                        'style' => "background-color: {$record->color}; color: #fff;",
                        'class' => 'rounded-md px-3 py-1 text-center font-medium',
                    ]),

                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data, $livewire) {
                        $data['is_active'] = true;

                        return $livewire->getRelationship()->create($data);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
