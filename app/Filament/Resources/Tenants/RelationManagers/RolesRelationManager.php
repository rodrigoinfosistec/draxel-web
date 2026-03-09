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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn ($rule) => $rule->where('tenant_id', $this->ownerRecord->id)
                    ),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('description')
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
                    ),

                Toggle::make('is_active')
                    ->label('Função ativa')
                    ->visible(fn ($operation) => $operation === 'edit'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->limit(40),

                IconColumn::make('is_active')
                    ->label('Ativa')
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
