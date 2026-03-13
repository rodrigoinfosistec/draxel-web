<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use App\Enums\SupportTicketStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupportTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Chamado')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('code')
                                    ->label('Código'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (SupportTicketStatus $state) => $state->label())
                                    ->color(fn (SupportTicketStatus $state) => match ($state) {
                                        SupportTicketStatus::OPEN => 'gray',
                                        SupportTicketStatus::IN_PROGRESS => 'warning',
                                        SupportTicketStatus::WAITING_CUSTOMER => 'info',
                                        SupportTicketStatus::WAITING_SUPPORT => 'danger',
                                        SupportTicketStatus::RESOLVED => 'success',
                                        SupportTicketStatus::CLOSED => 'gray',
                                    }),

                                TextEntry::make('tenant.name')
                                    ->label('Tenant'),

                                TextEntry::make('company.name')
                                    ->label('Empresa'),

                                TextEntry::make('creator.name')
                                    ->label('Solicitante'),

                                TextEntry::make('assignee.name')
                                    ->label('Responsável')
                                    ->placeholder('-'),

                                TextEntry::make('last_interaction_at')
                                    ->label('Última interação')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->placeholder('-'),

                                TextEntry::make('closed_at')
                                    ->label('Fechado em')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->placeholder('-'),
                            ]),

                        TextEntry::make('subject')
                            ->label('Assunto')
                            ->columnSpanFull(),

                        TextEntry::make('description')
                            ->label('Descrição inicial')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
