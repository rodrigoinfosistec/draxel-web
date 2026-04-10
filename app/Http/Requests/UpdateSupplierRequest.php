<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\Supplier $supplier */
        $supplier = $this->route('supplier');

        return $this->user()->can('update', $supplier);
    }

    public function rules(): array
    {
        /** @var \App\Models\Supplier $supplier */
        $supplier = $this->route('supplier');

        $tenantId = $this->user()->tenant_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document' => [
                'required',
                'string',
                'min:11',
                'max:14',
                Rule::unique('suppliers', 'document')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($supplier->id),
            ],
            'state_registration' => ['nullable', 'string', 'max:255'],
            'municipal_registration' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'zip_code' => ['nullable', 'string', 'size:8'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:30'],
            'complement' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'size:2'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $document = preg_replace('/\D+/', '', (string) $this->document);
        $zipCode = preg_replace('/\D+/', '', (string) $this->zip_code);

        $this->merge([
            'name' => trim((string) $this->name),
            'trade_name' => filled($this->trade_name) ? trim((string) $this->trade_name) : null,
            'document' => $document,
            'state_registration' => filled($this->state_registration) ? trim((string) $this->state_registration) : null,
            'municipal_registration' => filled($this->municipal_registration) ? trim((string) $this->municipal_registration) : null,
            'email' => filled($this->email) ? trim((string) $this->email) : null,
            'phone' => filled($this->phone) ? trim((string) $this->phone) : null,
            'mobile' => filled($this->mobile) ? trim((string) $this->mobile) : null,
            'zip_code' => filled($zipCode) ? $zipCode : null,
            'street' => filled($this->street) ? trim((string) $this->street) : null,
            'number' => filled($this->number) ? trim((string) $this->number) : null,
            'complement' => filled($this->complement) ? trim((string) $this->complement) : null,
            'district' => filled($this->district) ? trim((string) $this->district) : null,
            'city' => filled($this->city) ? trim((string) $this->city) : null,
            'state' => filled($this->state) ? strtoupper(trim((string) $this->state)) : null,
            'notes' => filled($this->notes) ? trim((string) $this->notes) : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'name' => 'razão social',
            'trade_name' => 'nome fantasia',
            'document' => 'documento',
            'state_registration' => 'inscrição estadual',
            'municipal_registration' => 'inscrição municipal',
            'email' => 'e-mail',
            'phone' => 'telefone',
            'mobile' => 'celular',
            'zip_code' => 'CEP',
            'street' => 'logradouro',
            'number' => 'número',
            'complement' => 'complemento',
            'district' => 'bairro',
            'city' => 'cidade',
            'state' => 'UF',
            'notes' => 'observações',
            'is_active' => 'status',
        ];
    }

    public function messages(): array
    {
        return [
            'document.unique' => 'Já existe um fornecedor com este documento.',
            'zip_code.size' => 'O CEP deve ter 8 dígitos.',
            'state.size' => 'A UF deve ter 2 caracteres.',
        ];
    }
}
