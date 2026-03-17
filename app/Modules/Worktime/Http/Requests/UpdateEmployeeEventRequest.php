<?php

namespace App\Modules\Worktime\Http\Requests;

use App\Modules\Worktime\Enums\EmployeeEventTimeMode;
use App\Modules\Worktime\Enums\EmployeeEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEmployeeEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('employeeEvent'));
    }

    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(fn ($query) => $query
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->where('company_id', session('current_company_id'))
                ),
            ],
            'is_partial' => ['required', 'boolean'],
            'event_type' => ['required', Rule::in(array_column(EmployeeEventType::cases(), 'value'))],
            'date' => ['required', 'date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $eventType = $this->input('event_type');

            if (! $eventType || ! in_array($eventType, array_column(EmployeeEventType::cases(), 'value'), true)) {
                return;
            }

            $type = EmployeeEventType::from($eventType);
            $isPartial = (bool) $this->boolean('is_partial');

            if ($type->timeMode() === EmployeeEventTimeMode::Partial && ! $isPartial) {
                $validator->errors()->add('is_partial', 'O tipo selecionado exige evento parcial.');
            }

            if ($type->timeMode() === EmployeeEventTimeMode::Day && $isPartial) {
                $validator->errors()->add('is_partial', 'O tipo selecionado exige evento diário.');
            }

            if ($isPartial) {
                if (! $this->filled('starts_at')) {
                    $validator->errors()->add('starts_at', 'A hora inicial é obrigatória para eventos parciais.');
                }

                if (! $this->filled('ends_at')) {
                    $validator->errors()->add('ends_at', 'A hora final é obrigatória para eventos parciais.');
                }
            }
        });
    }
}
