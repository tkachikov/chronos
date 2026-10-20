@inject('scheduleMonitor', 'Tkachikov\Chronos\Services\ScheduleMonitorService')
@php
    $schedules = $command->getModel()->schedules;
    $off = $schedules->where('run', false)->count();
@endphp
<section class="cx-card cx-schedules" id="schedules">
    <div class="cx-card-head">
        <h2>Schedules</h2>
        <span class="cx-count">{{ $schedules->count() }}</span>
        @if($off)
            <span class="cx-tag off">{{ $off }} off</span>
        @endif
        <div class="cx-head-end">
            @if($command->runInSchedule())
                @if($editing)
                    {{-- The window holds the edited schedule: a new one by the page without it --}}
                    <a class="cx-btn small" href="{{ route('chronos.edit', ['command' => $command->getModel(), 'new' => 1]) }}">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        Add schedule
                    </a>
                @else
                    <button type="button" class="cx-btn small" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        Add schedule
                    </button>
                @endif
            @else
                <span class="cx-tag off" title="The command is marked as not scheduled">Schedules are off for this command</span>
            @endif
        </div>
    </div>
    @if(!$schedules->count())
        <div class="cx-card-body cx-empty-note">
            @if($command->runInSchedule())
                The command is run manually only. Add a schedule to run it by the scheduler.
            @else
                The command is run manually only.
            @endif
        </div>
    @else
        <div class="cx-sched-cols">
            <div>Schedule</div>
            <div>Last run</div>
            <div>Next run</div>
            <div></div>
        </div>
        @foreach($schedules as $item)
            @php
                $args = $item->args ? $command->getArgumentsForExec($item->args) : '';
                $nextRunAt = $scheduleMonitor->getNextRunAt($item);
                $user = $item->user_id ? rescue(fn () => $item->userWithTrashed()->first(), null, false) : null;
                $current = $item->id == $schedule?->id;
            @endphp
            <div @class(['cx-sched', 'off' => !$item->run, 'active' => $current]) id="schedule-{{ $item->id }}">
                <div class="cx-sched-main">
                    <span @class(['cx-switch', 'off' => !$item->run]) title="{{ $item->run ? 'On' : 'Off' }}"></span>
                    <div class="cx-sched-text">
                        <div class="cx-sched-title">
                            <b>{{ $item->name ?: 'Schedule' }}</b>
                            <span class="cx-sched-id">#{{ $item->id }}</span>
                        </div>
                        <div class="cx-sched-time">
                            {{ $times[$item->time_method]->getDescription($item) }}
                            @if($args)
                                <code class="cx-code">{{ $args }}</code>
                            @endif
                        </div>
                        @if(!$item->run || $item->without_overlapping || $item->run_in_background || $user)
                            <div class="cx-sched-flags">
                                @if(!$item->run)
                                    <span class="cx-tag off">Off</span>
                                @endif
                                @if($item->without_overlapping)
                                    <span class="cx-tag" title="A run is skipped while the previous one is going on, the lock expires in {{ $item->without_overlapping_time }} minutes">Without overlapping · {{ $item->without_overlapping_time }} min</span>
                                @endif
                                @if($item->run_in_background)
                                    <span class="cx-tag" title="The scheduler does not wait for the command">In background</span>
                                @endif
                                @if($user)
                                    <span class="cx-tag" title="Saved by">{{ $user->email ?? $user->getKey() }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="cx-sched-run">
                    <span class="cx-k">Last run</span>
                    @if(!$item->last_status && !$item->last_skipped_at)
                        <span class="cx-none">Never</span>
                    @endif
                    @include('chronos::monitoring.last-run', ['item' => $item])
                </div>

                <div class="cx-sched-run">
                    <span class="cx-k">Next run</span>
                    @if($nextRunAt)
                        <div>
                            <span class="text-nowrap">{{ $nextRunAt->toDateTimeString() }}</span>
                            <small class="rel">{{ $nextRunAt->diffForHumans(['parts' => 2]) }}</small>
                        </div>
                    @else
                        <span class="cx-none">{{ $item->run ? '—' : 'Off' }}</span>
                    @endif
                </div>

                <div class="cx-sched-actions">
                    <button type="button" class="cx-btn ghost small icon" data-code-toggle aria-expanded="false" title="In code">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 7-5 5 5 5M16 7l5 5-5 5"/></svg>
                        <span class="cx-btn-text">Code</span>
                    </button>
                    <a @class(['cx-btn', 'ghost', 'small', 'icon', 'current' => $current]) href="{{ route('chronos.edit', ['command' => $command->getModel(), 'schedule' => $item->id]) }}" title="Edit">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        <span class="cx-btn-text">Edit</span>
                    </a>
                    <button type="button" class="cx-btn ghost small icon del" data-bs-toggle="modal" data-bs-target="#deleteModal_{{ $item->id }}" title="Delete">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>
                        <span class="cx-btn-text">Delete</span>
                    </button>
                    @include('chronos::delete-modal', ['id' => 'deleteModal_'.$item->id, 'action' => route('chronos.schedules.destroy', ['command' => $command->getModel(), 'schedule' => $item->id])])
                </div>

                <div class="cx-sched-code" hidden>
                    @include('chronos::schedules.in_code', ['item' => $item, 'command' => $command])
                </div>
            </div>
        @endforeach
    @endif
</section>
@include('chronos::schedules.edit', ['command' => $command, 'schedule' => $schedule, 'times' => $times])
