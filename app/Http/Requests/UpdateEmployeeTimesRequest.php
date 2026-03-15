<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeTimesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return $this->user()?->can('update', $employee) ?? false;
    }

    public function rules(): array
    {
        return [
            'times' => ['required', 'array', 'size:7'],
            'times.*.weekday' => ['required', 'string', 'max:20'],
            'times.*.weekday_label' => ['nullable', 'string', 'max:50'],
            'times.*.start_time' => ['nullable', 'date_format:H:i'],
            'times.*.end_time' => ['nullable', 'date_format:H:i'],
            'times.*.break_duration' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                foreach ($this->input('times', []) as $index => $time) {
                    $startTime = $time['start_time'] ?? null;
                    $endTime = $time['end_time'] ?? null;

                    if (($startTime && ! $endTime) || (! $startTime && $endTime)) {
                        $validator->errors()->add(
                            "times.{$index}.start_time",
                            'Início e fim devem ser informados juntos.'
                        );
                    }
                }
            },
        ];
    }
}
