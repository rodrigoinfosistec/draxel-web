<?php

namespace App\Modules\Worktime\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHourBankSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('worktime.createHourBankSnapshot') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'period_start' => 'data inicial',
            'period_end' => 'data final',
            'notes' => 'observações',
        ];
    }

    public function messages(): array
    {
        return [
            'period_end.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
        ];
    }
}
