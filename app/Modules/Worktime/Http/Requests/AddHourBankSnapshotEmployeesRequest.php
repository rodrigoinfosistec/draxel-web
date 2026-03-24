<?php

namespace App\Modules\Worktime\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddHourBankSnapshotEmployeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('worktime.updateHourBankSnapshot') ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['required', 'integer', 'distinct', 'exists:employees,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_ids' => 'funcionários',
            'employee_ids.*' => 'funcionário',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_ids.required' => 'Selecione ao menos um funcionário.',
            'employee_ids.array' => 'Os funcionários informados são inválidos.',
            'employee_ids.min' => 'Selecione ao menos um funcionário.',
            'employee_ids.*.distinct' => 'Existem funcionários duplicados na seleção.',
            'employee_ids.*.exists' => 'Um ou mais funcionários selecionados não existem.',
        ];
    }
}
