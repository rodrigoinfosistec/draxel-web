<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('event')
                    ->label('Evento')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

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

                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subject_type')
                    ->label('Entidade')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? Str::afterLast($state, '\\') : '-')
                    ->searchable(),

                TextColumn::make('subject_id')
                    ->label('ID entidade')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('method')
                    ->label('Método')
                    ->badge()
                    ->color(fn (?string $state): string => match (strtoupper((string) $state)) {
                        'GET' => 'info',
                        'POST' => 'success',
                        'PUT', 'PATCH' => 'warning',
                        'DELETE' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('route')
                    ->label('Rota')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->route)
                    ->searchable(),

                TextColumn::make('ip')
                    ->label('IP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
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

                SelectFilter::make('user_id')
                    ->label('Usuário')
                    ->options(fn () => User::query()
                        ->withoutGlobalScopes()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),

                SelectFilter::make('event')
                    ->label('Evento')
                    ->options([
                        'created' => 'created',
                        'updated' => 'updated',
                        'deleted' => 'deleted',
                    ])
                    ->multiple(),

                SelectFilter::make('method')
                    ->label('Método')
                    ->options([
                        'GET' => 'GET',
                        'POST' => 'POST',
                        'PUT' => 'PUT',
                        'PATCH' => 'PATCH',
                        'DELETE' => 'DELETE',
                    ])
                    ->multiple(),

                Filter::make('created_at')
                    ->label('Período')
                    ->schema([
                        DatePicker::make('from')->label('De'),
                        DatePicker::make('until')->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Filter::make('advanced')
                    ->label('Busca avançada')
                    ->schema([
                        TextInput::make('subject_type')->label('Entidade'),
                        TextInput::make('subject_id')->label('ID da entidade'),
                        TextInput::make('route')->label('Rota'),
                        TextInput::make('ip')->label('IP'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['subject_type'] ?? null, fn (Builder $query, $value) => $query->where('subject_type', 'ilike', "%{$value}%"))
                            ->when($data['subject_id'] ?? null, fn (Builder $query, $value) => $query->where('subject_id', 'ilike', "%{$value}%"))
                            ->when($data['route'] ?? null, fn (Builder $query, $value) => $query->where('route', 'ilike', "%{$value}%"))
                            ->when($data['ip'] ?? null, fn (Builder $query, $value) => $query->where('ip', 'ilike', "%{$value}%"));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
