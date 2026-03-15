<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('holidays.create') ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('holidays', 'name')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('date', $this->date)),
            ],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }
}
