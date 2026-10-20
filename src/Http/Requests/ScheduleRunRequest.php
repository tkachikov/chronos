<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Tkachikov\Chronos\Helpers\ArgsHelper;

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
        $this->merge(['args' => ArgsHelper::clean($this->collect('args'))]);
    }
}
