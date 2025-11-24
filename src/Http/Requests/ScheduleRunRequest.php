<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ScheduleRunRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'args' => ['nullable'],
        ];
    }

    #[\Override]
    protected function prepareForValidation(): void
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
}
