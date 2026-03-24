<?php

namespace App\Modules\Worktime\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHourBankSnapshotAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('worktime.updateHourBankSnapshot') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'adjustment_minutes' => $this->adjustment_minutes === '' || $this->adjustment_minutes === null
                ? 0
                : (int) $this->adjustment_minutes,
            'adjustment_reason' => filled($this->adjustment_reason)
                ? trim((string) $this->adjustment_reason)
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'adjustment_minutes' => ['required', 'integer', 'min:-1440', 'max:1440'],
            'adjustment_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'adjustment_minutes' => 'ajuste em minutos',
            'adjustment_reason' => 'motivo do ajuste',
        ];
    }

    public function messages(): array
    {
        return [
            'adjustment_minutes.min' => 'O ajuste mínimo permitido é de -1440 minutos.',
            'adjustment_minutes.max' => 'O ajuste máximo permitido é de 1440 minutos.',
        ];
    }
}
