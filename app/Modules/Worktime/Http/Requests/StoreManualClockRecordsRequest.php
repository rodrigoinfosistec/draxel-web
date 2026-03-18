<?php

namespace App\Modules\Worktime\Http\Requests;

use App\Modules\Worktime\Models\ClockRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreManualClockRecordsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ClockRecord::class);
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
            'date' => ['required', 'date'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.time' => ['required', 'date_format:H:i'],
            'rows.*.notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $times = collect($this->input('rows', []))
                ->pluck('time')
                ->filter();

            if ($times->count() !== $times->unique()->count()) {
                $validator->errors()->add('rows', 'Não repita horários iguais para o mesmo funcionário no mesmo dia.');
            }
        });
    }
}
