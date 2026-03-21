<?php

namespace App\Modules\Worktime\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankHourEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('worktime.createBankHourEntry') ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer'],
            'entry_type' => ['required', 'in:manual_credit,manual_debit'],
            'hours' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'occurred_on' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
