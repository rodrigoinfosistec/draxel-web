<?php

namespace App\Modules\Worktime\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('worktime.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer'],
            'event_type' => [
                'required',
                Rule::in([
                    'medical_certificate',
                    'day_off',
                    'suspension',
                    'vacation',
                    'leave',
                    'compensation',
                    'declaration',
                ]),
            ],
            'input_mode' => ['required', Rule::in(['schedule_day', 'custom_period'])],
            'date' => ['nullable', 'date', Rule::requiredIf(fn () => $this->input('input_mode') === 'schedule_day')],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'ends_at.after' => 'A data/hora final deve ser maior que a data/hora inicial.',
        ];
    }
}
