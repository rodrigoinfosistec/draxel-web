<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('clients.create') ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('clients', 'document')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'document' => 'documento',
            'email' => 'e-mail',
            'phone' => 'telefone',
            'address' => 'endereço',
            'notes' => 'observações',
        ];
    }
}
