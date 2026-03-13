<?php

namespace App\Filament\Resources\SupportTickets\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Conversa';
    protected static ?string $label = 'Mensagem';
    protected static ?string $pluralLabel = 'Mensagens';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes()
                ->with([
                    'user' => fn ($userQuery) => $userQuery->withoutGlobalScopes(),
                ])
                ->oldest('created_at')
            )
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Autor')
                    ->placeholder('-')
                    ->searchable(),

                IconColumn::make('is_internal')
                    ->label('Interna')
                    ->boolean(),

                TextColumn::make('message')
                    ->label('Mensagem')
                    ->wrap(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
