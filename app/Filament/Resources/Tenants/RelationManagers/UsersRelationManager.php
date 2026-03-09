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
use Illuminate\Support\Facades\Hash;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn ($rule) => $rule->where('tenant_id', $this->ownerRecord->id)
                    ),

                TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    ->required(fn ($operation) => $operation === 'create')
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state)),

                CheckboxList::make('companies')
                    ->label('Empresas vinculadas')
                    ->relationship(
                        name: 'companies',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query
                            ->withoutTenant()
                            ->where('companies.tenant_id', $this->ownerRecord->id)
                    )
                    ->pivotData([
                        'tenant_id' => $this->ownerRecord->id,
                    ]),

                CheckboxList::make('roles')
                    ->label('Funções vinculadas')
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query
                            ->where('roles.tenant_id', $this->ownerRecord->id)
                            ->where('roles.is_active', true)
                    ),

                CheckboxList::make('modules')
                    ->label('Módulos vinculados')
                    ->relationship(
                        name: 'modules',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query
                            ->where('modules.is_active', true)
                            ->whereHas('tenants', fn ($tenantQuery) => $tenantQuery
                                ->where('tenants.id', $this->ownerRecord->id)
                                ->where('tenant_modules.is_active', true)
                            )
                    ),

                Toggle::make('is_active')
                    ->label('Usuário ativo')
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

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('companies.name')
                    ->label('Empresas')
                    ->badge(),

                TextColumn::make('roles.name')
                    ->label('Funções')
                    ->badge(),

                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data, $livewire) {
                        $data['default_company_id'] = null;
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
