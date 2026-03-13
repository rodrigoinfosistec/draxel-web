<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Enums\SupportTicketStatus;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicketMessage;
use App\Notifications\SupportTicketUpdatedNotification;
use App\Support\Audit\Audit;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignToMe')
                ->label('Assumir')
                ->icon('heroicon-o-user')
                ->color('gray')
                ->visible(fn () => blank($this->record->assigned_to) || $this->record->assigned_to !== Auth::id())
                ->action(function (): void {
                    $this->record->update([
                        'assigned_to' => Auth::id(),
                        'status' => SupportTicketStatus::IN_PROGRESS,
                        'is_open' => true,
                        'closed_at' => null,
                        'last_interaction_at' => now(),
                    ]);

                    Audit::event('support_tickets.assigned', $this->record, [
                        'assigned_to' => Auth::id(),
                    ]);

                    Notification::make()
                        ->title('Chamado assumido com sucesso.')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Action::make('changeStatus')
                ->label('Alterar status')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options(collect(SupportTicketStatus::cases())
                            ->mapWithKeys(fn (SupportTicketStatus $status) => [$status->value => $status->label()])
                            ->all())
                        ->required(),
                ])
                ->fillForm(fn (): array => [
                    'status' => $this->record->status->value,
                ])
                ->action(function (array $data): void {
                    $status = SupportTicketStatus::from($data['status']);

                    $this->record->update([
                        'status' => $status,
                        'is_open' => $status->isOpen(),
                        'closed_at' => $status->isOpen() ? null : now(),
                        'last_interaction_at' => now(),
                    ]);

                    Audit::event('support_tickets.status_changed', $this->record, [
                        'status' => $status->value,
                    ]);

                    $creator = $this->record->creator()->withoutGlobalScopes()->first();

                    $creator?->notify(
                        new SupportTicketUpdatedNotification($this->record, 'status_changed')
                    );

                    Notification::make()
                        ->title('Status atualizado com sucesso.')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Action::make('reply')
                ->label('Responder')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('primary')
                ->schema([
                    Textarea::make('message')
                        ->label('Mensagem')
                        ->rows(6)
                        ->required(),

                    Select::make('status')
                        ->label('Novo status')
                        ->options(collect(SupportTicketStatus::cases())
                            ->mapWithKeys(fn (SupportTicketStatus $status) => [$status->value => $status->label()])
                            ->all())
                        ->default(SupportTicketStatus::WAITING_CUSTOMER->value)
                        ->required(),

                    Select::make('is_internal')
                        ->label('Tipo da resposta')
                        ->options([
                            0 => 'Mensagem para o cliente',
                            1 => 'Nota interna',
                        ])
                        ->default(0)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    DB::transaction(function () use ($data): void {
                        $message = SupportTicketMessage::query()->withoutGlobalScopes()->create([
                            'tenant_id' => $this->record->tenant_id,
                            'company_id' => $this->record->company_id,
                            'support_ticket_id' => $this->record->id,
                            'user_id' => Auth::id(),
                            'message' => $data['message'],
                            'is_internal' => (bool) $data['is_internal'],
                        ]);

                        $status = SupportTicketStatus::from($data['status']);

                        $this->record->update([
                            'assigned_to' => $this->record->assigned_to ?? Auth::id(),
                            'status' => $status,
                            'is_open' => $status->isOpen(),
                            'closed_at' => $status->isOpen() ? null : now(),
                            'last_interaction_at' => now(),
                        ]);

                        Audit::event('support_tickets.replied_by_admin', $this->record, [
                            'message_id' => $message->id,
                            'status' => $status->value,
                            'is_internal' => (bool) $data['is_internal'],
                        ]);
                    });

                    if (! (bool) $data['is_internal']) {
                        $creator = $this->record->creator()->withoutGlobalScopes()->first();

                        $creator?->notify(
                            new SupportTicketUpdatedNotification($this->record, 'replied', $data['message'])
                        );
                    }

                    Notification::make()
                        ->title('Resposta registrada com sucesso.')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}
