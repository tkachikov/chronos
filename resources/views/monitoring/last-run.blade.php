@inject('scheduleMonitor', 'Tkachikov\Chronos\Services\ScheduleMonitorService')
@php
    $status = \Tkachikov\Chronos\Enums\ScheduleStatusEnum::tryFrom((string) $item->last_status);
    $missedSince = $scheduleMonitor->getMissedSince($item);
    $lockState = $scheduleMonitor->getLockState($item);
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
@if($lockState === \Tkachikov\Chronos\Services\ScheduleMonitorService::LOCK_STUCK)
    <span class="badge bg-danger" title="The runs are skipped by withoutOverlapping, but nothing is running. Release the lock by php artisan schedule:clear-cache">
        Lock is stuck, skipped {{ $item->skipped_count }} times
    </span>
@elseif($lockState && !$compact)
    <div class="text-nowrap small text-muted" title="The previous run is going on, the next runs are skipped by withoutOverlapping">
        Skipped {{ $item->skipped_count }} times, last {{ $item->last_skipped_at?->toDateTimeString() }}
    </div>
@endif
@if($missedSince)
    <span class="badge bg-warning text-dark" title="The run due at {{ $missedSince->toDateTimeString() }} and the next ones have not started">
        Missed since {{ $missedSince->toDateTimeString() }}
    </span>
@endif
