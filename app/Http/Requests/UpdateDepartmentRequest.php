<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Department $department */
        $department = $this->route('department');

        return $this->user()?->can('update', $department) ?? false;
    }

    public function rules(): array
    {
        /** @var Department $department */
        $department = $this->route('department');

        $tenantId = $this->user()->tenant_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'slug')
                    ->ignore($department->id)
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}
