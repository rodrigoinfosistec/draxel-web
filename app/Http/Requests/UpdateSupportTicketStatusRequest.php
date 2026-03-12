<?php

namespace App\Http\Requests;

use App\Enums\SupportTicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateSupportTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('supportTicket');

        return $ticket && $this->user()->can('changeStatus', $ticket);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(SupportTicketStatus::class)],
        ];
    }
}
