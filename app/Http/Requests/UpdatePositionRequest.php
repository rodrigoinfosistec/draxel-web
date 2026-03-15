<?php

namespace App\Http\Requests;

use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Position $position */
        $position = $this->route('position');

        return $this->user()?->can('update', $position) ?? false;
    }

    public function rules(): array
    {
        /** @var Position $position */
        $position = $this->route('position');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'slug')->ignore($position->id),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}
