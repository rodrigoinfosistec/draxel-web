<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCompanyHourBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('parameters.update');
    }

    public function rules(): array
    {
        return [
            'uses_hour_bank' => ['required', 'boolean'],
            'hour_bank_starts_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $usesHourBank = (bool) $this->boolean('uses_hour_bank');

            if ($usesHourBank && ! $this->filled('hour_bank_starts_at')) {
                $validator->errors()->add(
                    'hour_bank_starts_at',
                    'A data inicial é obrigatória quando o banco de horas está habilitado.'
                );
            }

            if (! $usesHourBank && $this->filled('hour_bank_starts_at')) {
                $validator->errors()->add(
                    'hour_bank_starts_at',
                    'A data inicial só deve ser informada quando o banco de horas está habilitado.'
                );
            }
        });
    }
}
