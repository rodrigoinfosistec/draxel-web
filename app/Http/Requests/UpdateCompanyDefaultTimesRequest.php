<?php

namespace App\Http\Requests;

use App\Enums\Weekday;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyDefaultTimesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'times' => ['required', 'array', 'size:7'],

            'times.*.weekday' => [
                'required',
                'string',
                Rule::in(Weekday::values()),
            ],

            'times.*.start_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'times.*.end_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'times.*.break_duration' => [
                'nullable',
                'date_format:H:i',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'times.required' => 'Os horários padrão são obrigatórios.',
            'times.size' => 'Devem existir exatamente 7 dias cadastrados.',
            'times.*.weekday.required' => 'O dia da semana é obrigatório.',
            'times.*.weekday.in' => 'O dia da semana informado é inválido.',
            'times.*.start_time.date_format' => 'O horário inicial deve estar no formato HH:MM.',
            'times.*.end_time.date_format' => 'O horário final deve estar no formato HH:MM.',
            'times.*.break_duration.date_format' => 'O intervalo deve estar no formato HH:MM.',
        ];
    }
}
