<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected SupportTicket $ticket,
        protected string $action,
        protected ?string $messageText = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->action) {
            'created' => 'Chamado aberto: ' . $this->ticket->code,
            'replied' => 'Nova atualização no chamado: ' . $this->ticket->code,
            'status_changed' => 'Status atualizado no chamado: ' . $this->ticket->code,
            default => 'Atualização no chamado: ' . $this->ticket->code,
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Seu chamado de suporte recebeu uma atualização.')
            ->line('Código: ' . $this->ticket->code)
            ->line('Assunto: ' . $this->ticket->subject)
            ->line('Status atual: ' . $this->ticket->status->label());

        if ($this->action === 'created') {
            $mail->line('Seu chamado foi aberto com sucesso e nossa equipe acompanhará a solicitação.');
        }

        if ($this->action === 'replied' && $this->messageText) {
            $mail->line('Nova resposta:')
                ->line($this->messageText);
        }

        if ($this->action === 'status_changed') {
            $mail->line('O status do chamado foi alterado.');
        }

        return $mail->line('Acesse o sistema para acompanhar os detalhes do chamado.');
    }
}
