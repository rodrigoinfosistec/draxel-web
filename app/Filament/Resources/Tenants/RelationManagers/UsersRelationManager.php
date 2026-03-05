<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\CheckboxList;
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
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->password()
                    ->required(fn ($operation) => $operation === 'create')
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->label('Senha'),

                Select::make('default_company_id')
                    ->label('Empresa padrão')
                    ->options(
                        fn () => $this->ownerRecord
                            ->companies()
                            ->pluck('name', 'id')
                    )
                    ->required(),

                CheckboxList::make('companies')
                    ->label('Empresas vinculadas')
                    ->relationship(
                        name: 'companies',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) =>
                            $query->where('companies.tenant_id', $this->ownerRecord->id)
                    )
                    ->pivotData(['tenant_id' => $this->ownerRecord->id,]),

                Toggle::make('is_admin')
                    ->label('Administrador')
                    ->default(false),

                Toggle::make('is_active')
                ->label('Usuário ativo')
                ->default(true)
                ->visible(fn ($operation) => $operation === 'edit'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('defaultCompany.name')
                    ->label('Empresa padrão')
                    ->sortable(),

                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
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
