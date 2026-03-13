<?php

namespace App\Filament\Resources\SupportTickets\Tables;

use App\Enums\SupportTicketStatus;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_interaction_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subject')
                    ->label('Assunto')
                    ->searchable()
                    ->limit(60),

                TextColumn::make('status')
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
                    })
                    ->sortable(),

                IconColumn::make('is_open')
                    ->label('Aberto')
                    ->boolean(),

                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company.name')
                    ->label('Empresa')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('Solicitante')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('assignee.name')
                    ->label('Responsável')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('last_interaction_at')
                    ->label('Última interação')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->options(fn () => Tenant::query()
                        ->withoutGlobalScopes()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),

                SelectFilter::make('company_id')
                    ->label('Empresa')
                    ->options(fn () => Company::query()
                        ->withoutGlobalScopes()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),

                SelectFilter::make('created_by')
                    ->label('Solicitante')
                    ->options(fn () => User::query()
                        ->withoutGlobalScopes()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),

                SelectFilter::make('assigned_to')
                    ->label('Responsável')
                    ->options(fn () => User::query()
                        ->withoutGlobalScopes()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(SupportTicketStatus::cases())
                        ->mapWithKeys(fn (SupportTicketStatus $status) => [$status->value => $status->label()])
                        ->all()),

                Filter::make('is_open')
                    ->label('Somente abertos')
                    ->query(fn (Builder $query) => $query->where('is_open', true)),

                Filter::make('periodo')
                    ->label('Período')
                    ->schema([
                        DatePicker::make('from')->label('De'),
                        DatePicker::make('until')->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
