<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleRunRequest extends FormRequest
{
    public function prepareForValidation(): void
    {
        $args = [];
        foreach ($this->collect('args') as $key => $value) {
            if (is_null($value)) {
                continue;
            }
            $args[$key] = $value === 'on' ?: $value;
        }
        $this->merge(['args' => $args]);
    }

    public function rules(): array
    {
        return [
            'args' => ['nullable'],
        ];
    }
}
