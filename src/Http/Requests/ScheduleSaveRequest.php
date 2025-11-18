<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Http\Requests;

use Tkachikov\Chronos\Models\Command;
use Illuminate\Foundation\Http\FormRequest;
use Tkachikov\Chronos\Models\Schedule;

class ScheduleSaveRequest extends FormRequest
{
    public function prepareForValidation(): void
    {
        $this->merge([
            'run' => $this->boolean('run'),
            'without_overlapping' => $this->boolean('without_overlapping'),
            'run_in_background' => $this->boolean('run_in_background'),
        ]);
        $args = [];
        foreach ($this->collect('args') as $key => $value) {
            $args[$key] = $value === 'on' ?: $value;
        }
        $this->merge(['args' => $args]);
        $this->merge(['time_params' => $this->input('time_params')[$this->input('time_method')] ?? null]);
    }

    public function rules(): array
    {
        return [
            'command_id' => ['exists:'.(new Command)->getTable().',id'],
            'id' => ['nullable', 'exists:'.(new Schedule)->getTable().',id'],
            'run' => ['required', 'boolean'],
            'without_overlapping' => ['required', 'boolean'],
            'without_overlapping_time' => ['required', 'integer'],
            'run_in_background' => ['required', 'boolean'],
            'time_method' => ['required', 'string'],
            'time_params' => ['nullable', 'array', 'min:0', 'max:3'],
            'args' => ['nullable'],
        ];
    }
}
