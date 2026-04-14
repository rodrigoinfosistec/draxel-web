<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    protected static ?string $title = 'Funções';
    protected static ?string $label = 'Função';
    protected static ?string $pluralLabel = 'Funções';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn ($rule) => $rule->where('tenant_id', $this->ownerRecord->id)
                    ),

                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                TextInput::make('description')
                    ->label('Descrição')
                    ->maxLength(65535),

                CheckboxList::make('permissions')
                    ->label('Permissões vinculadas')
                    ->relationship(
                        name: 'permissions',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query
                            ->where('permissions.is_active', true)
                            ->whereHas('module', fn ($moduleQuery) => $moduleQuery
                                ->where('modules.is_active', true)
                                ->whereHas('tenants', fn ($tenantQuery) => $tenantQuery
                                    ->where('tenants.id', $this->ownerRecord->id)
                                    ->where('tenant_modules.is_active', true)
                                )
                            )
                            ->orderBy('permissions.name')
                    ),

                Toggle::make('is_active')
                    ->label('Função ativa')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(40),

                IconColumn::make('is_active')
                    ->label('Ativa')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn () => true)
                    ->using(function (array $data, $livewire) {
                        $data['is_active'] = $data['is_active'] ?? true;

                        return $livewire->getRelationship()->create($data);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn () => true),

                DeleteAction::make()
                    ->authorize(fn () => true),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(fn () => true),
                ]),
            ]);
    }
}
