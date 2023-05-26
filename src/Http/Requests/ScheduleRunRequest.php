<?php
declare(strict_types=1);

namespace Tkachikov\LaravelCommands\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleRunRequest extends FormRequest
{
    /**
     * @return void
     */
    public function prepareForValidation(): void
    {
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
            'args' => ['nullable'],
        ];
    }
}
