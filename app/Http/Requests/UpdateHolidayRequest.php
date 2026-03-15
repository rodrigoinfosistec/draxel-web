<?php

namespace App\Http\Requests;

use App\Models\Holiday;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Holiday $holiday */
        $holiday = $this->route('holiday');

        return $this->user()?->can('update', $holiday) ?? false;
    }

    public function rules(): array
    {
        /** @var Holiday $holiday */
        $holiday = $this->route('holiday');

        $tenantId = $this->user()->tenant_id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('holidays', 'name')
                    ->ignore($holiday->id)
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('date', $this->date)),
            ],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }
}
