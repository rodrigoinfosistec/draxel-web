<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplySupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('supportTicket');

        return $ticket && $this->user()->can('reply', $ticket);
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string'],
            'is_internal' => ['nullable', 'boolean'],
        ];
    }
}
