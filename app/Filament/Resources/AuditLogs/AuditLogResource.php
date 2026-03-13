<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Filament\Resources\AuditLogs\Schemas\AuditLogInfolist;
use App\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'event';

    protected static ?string $navigationLabel = 'Auditoria';

    protected static ?string $modelLabel = 'Log de auditoria';

    protected static ?string $pluralModelLabel = 'Logs de auditoria';

    protected static ?int $navigationSort = 80;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return AuditLog::query()
            ->withoutGlobalScopes()
            ->with([
                'tenant' => fn ($query) => $query->withoutGlobalScopes(),
                'company' => fn ($query) => $query->withoutGlobalScopes(),
                'user' => fn ($query) => $query->withoutGlobalScopes(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AuditLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}
