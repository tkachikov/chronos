@inject('heartbeat', 'Tkachikov\Chronos\Services\SchedulerHeartbeatService')
@if(rescue(fn () => $heartbeat->isStopped(), false))
    @php($lastRunAt = $heartbeat->getLastRunAt())
    <div class="alert alert-danger shadow-sm" role="alert">
        @if($lastRunAt)
            <b>The scheduler is not running:</b> the last <code>schedule:run</code> was {{ $lastRunAt->diffForHumans() }} ({{ $lastRunAt->toDateTimeString() }}).
        @else
            <b>The scheduler is not running:</b> <code>schedule:run</code> has never run.
        @endif
        Enabled schedules are not run. Check that cron runs <code>php artisan schedule:run</code> every minute or <code>php artisan schedule:work</code> is running.
    </div>
@endif
