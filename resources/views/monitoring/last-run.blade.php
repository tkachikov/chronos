@inject('scheduleMonitor', 'Tkachikov\Chronos\Services\ScheduleMonitorService')
@php
    $status = \Tkachikov\Chronos\Enums\ScheduleStatusEnum::tryFrom((string) $item->last_status);
    $missedSince = $scheduleMonitor->getMissedSince($item);
    $compact ??= false;
@endphp
@if($status && !$compact)
    <div class="text-nowrap" title="{{ $item->last_finished_at ? 'Finished at ' . $item->last_finished_at->toDateTimeString() : '' }}">
        <span class="{{ $status->getCssClass() }}">{{ ucfirst($status->value) }}</span>
        <small class="text-muted">{{ $item->last_run_at?->toDateTimeString() }}</small>
    </div>
@elseif($status === \Tkachikov\Chronos\Enums\ScheduleStatusEnum::FAILED)
    <span class="badge bg-danger" title="{{ $item->last_run_at?->toDateTimeString() }}">Last run failed</span>
@endif
@if($missedSince)
    <span class="badge bg-warning text-dark" title="The run due at {{ $missedSince->toDateTimeString() }} and the next ones have not started">
        Missed since {{ $missedSince->toDateTimeString() }}
    </span>
@endif
