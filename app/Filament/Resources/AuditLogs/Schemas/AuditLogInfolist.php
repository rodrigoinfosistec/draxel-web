<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AuditLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Auditoria')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('#'),

                                TextEntry::make('event')
                                    ->label('Evento')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'created' => 'success',
                                        'updated' => 'warning',
                                        'deleted' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('tenant.name')
                                    ->label('Tenant'),

                                TextEntry::make('company.name')
                                    ->label('Empresa'),

                                TextEntry::make('user.name')
                                    ->label('Usuário'),

                                TextEntry::make('created_at')
                                    ->label('Data')
                                    ->dateTime('d/m/Y H:i:s'),

                                TextEntry::make('subject_type')
                                    ->label('Entidade')
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? Str::afterLast($state, '\\') : '-'),

                                TextEntry::make('subject_id')
                                    ->label('ID da entidade')
                                    ->placeholder('-'),

                                TextEntry::make('method')
                                    ->label('Método')
                                    ->badge()
                                    ->color(fn (?string $state): string => match (strtoupper((string) $state)) {
                                        'GET' => 'info',
                                        'POST' => 'success',
                                        'PUT', 'PATCH' => 'warning',
                                        'DELETE' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('ip')
                                    ->label('IP')
                                    ->placeholder('-'),
                            ]),
                    ]),

                Section::make('Requisição')
                    ->schema([
                        TextEntry::make('route')
                            ->label('Rota')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Properties')
                    ->schema([
                        KeyValueEntry::make('properties')
                            ->label('Dados')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
