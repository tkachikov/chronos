<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tkachikov\Chronos\Enums\RunsInEnum;
use Tkachikov\Chronos\Models\CommandMetric;

final class IndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'runsIn' => ['nullable', Rule::enum(RunsInEnum::class)],
            'sortKey' => ['nullable', 'string', Rule::in(CommandMetric::$sortKeys)],
            'sortBy' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}