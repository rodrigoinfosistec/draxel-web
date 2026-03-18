<?php

namespace App\Modules\Worktime\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClockRecordImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('worktime.createClockRecordImport');
    }

    public function rules(): array
    {
        return [
            'tenant_clock_device_id' => [
                'required',
                'integer',
                Rule::exists('tenant_clock_devices', 'id')->where(fn ($query) => $query
                    ->where('tenant_id', $this->user()->tenant_id)
                ),
            ],
            'file' => ['required', 'file', 'mimes:txt', 'max:5120'],
        ];
    }
}
