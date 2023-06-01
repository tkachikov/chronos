<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Http\Requests;

use Tkachikov\LaravelPulse\Models\Command;
use Illuminate\Foundation\Http\FormRequest;
use Tkachikov\LaravelPulse\Models\Schedule;

class ScheduleSaveRequest extends FormRequest
{
    /**
     * @return void
     */
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
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'command_id' => ['exists:'.(new Command)->getTable().',id'],
            'id' => ['nullable', 'exists:'.(new Schedule)->getTable().',id'],
            'run' => ['required', 'boolean'],
            'without_overlapping' => ['required', 'boolean'],
            'run_in_background' => ['required', 'boolean'],
            'time_method' => ['required', 'string'],
            'time_params' => ['nullable', 'string'],
            'args' => ['nullable'],
        ];
    }
}
