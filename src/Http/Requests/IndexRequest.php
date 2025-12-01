<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tkachikov\Chronos\Enums\LastRunStateFilterEnum;
use Tkachikov\Chronos\Enums\RunsInFilterEnum;
use Tkachikov\Chronos\Enums\SchedulersFilterEnum;
use Tkachikov\Chronos\Models\CommandMetric;
use Tkachikov\Chronos\Repositories\TimeRepositoryInterface;

final class IndexRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var TimeRepositoryInterface $timeRepository */
        $timeRepository = app(TimeRepositoryInterface::class);

        return [
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'runsIn' => [
                'nullable',
                Rule::enum(RunsInFilterEnum::class),
            ],
            'scheduleMethod' => [
                'nullable',
                Rule::in(array_keys($timeRepository->get())),
            ],
            'schedulers' => [
                'nullable',
                Rule::enum(SchedulersFilterEnum::class),
            ],
            'lastRunState' => [
                'nullable',
                Rule::enum(LastRunStateFilterEnum::class),
            ],
            'sortKey' => [
                'nullable',
                'string',
                Rule::in(CommandMetric::$sortKeys),
            ],
            'sortBy' => [
                'nullable',
                'string',
                Rule::in(['asc', 'desc']),
            ],
        ];
    }
}
