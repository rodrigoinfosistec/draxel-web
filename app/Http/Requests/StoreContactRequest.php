<?php

namespace App\Http\Requests;

use App\Enums\ContactItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('contacts.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function (array $item, int $index) {
                $value = (string) ($item['value'] ?? '');
                $type = (string) ($item['type'] ?? '');

                if (in_array($type, ['commercial_phone', 'home_phone', 'cellphone', 'whatsapp'], true)) {
                    $value = preg_replace('/\D+/', '', $value);
                }

                return [
                    'type' => $type,
                    'value' => trim($value),
                    'label' => filled($item['label'] ?? null) ? trim((string) $item['label']) : null,
                    'sort_order' => $index,
                ];
            })
            ->all();

        $this->merge([
            'name' => trim((string) $this->name),
            'description' => filled($this->description) ? trim((string) $this->description) : null,
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', Rule::enum(ContactItemType::class)],
            'items.*.value' => ['required', 'string', 'max:255'],
            'items.*.label' => ['nullable', 'string', 'max:255'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
