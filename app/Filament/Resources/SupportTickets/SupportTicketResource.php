<?php

namespace App\Filament\Resources\SupportTickets;

use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Filament\Resources\SupportTickets\RelationManagers\MessagesRelationManager;
use App\Filament\Resources\SupportTickets\Schemas\SupportTicketInfolist;
use App\Filament\Resources\SupportTickets\Tables\SupportTicketsTable;
use App\Models\SupportTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?string $navigationLabel = 'Chamados';

    protected static ?string $modelLabel = 'Chamado';

    protected static ?string $pluralModelLabel = 'Chamados';

    protected static ?int $navigationSort = 90;

    public static function getEloquentQuery(): Builder
    {
        return SupportTicket::query()
            ->withoutGlobalScopes()
            ->with([
                'tenant' => fn ($query) => $query->withoutGlobalScopes(),
                'company' => fn ($query) => $query->withoutGlobalScopes(),
                'creator' => fn ($query) => $query->withoutGlobalScopes(),
                'assignee' => fn ($query) => $query->withoutGlobalScopes(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupportTicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportTicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'view' => ViewSupportTicket::route('/{record}'),
        ];
    }
}
