@php
    $runFilters = [
        '' => ['All', $runCounts['all'], ''],
        'failed' => ['Failed', $runCounts['failed'], 'bad'],
        'running' => ['Running', $runCounts['running'], ''],
    ];
    $filterUrl = fn (string $value): string => request()->fullUrlWithQuery(['runs' => $value ?: null, 'page' => null]) . '#runs';
@endphp
<section class="cx-card cx-runs" id="runs">
    <div class="cx-card-head">
        <h2>Runs</h2>
        <span class="cx-count">{{ $runCounts['all'] }}</span>
        @if($runCounts['all'])
            <nav class="cx-run-filters" aria-label="Runs">
                @foreach($runFilters as $value => [$title, $number, $css])
                    @if($value === '' || $number || $runsFilter === $value)
                        <a href="{{ $filterUrl($value) }}" @class(['active' => $runsFilter === $value, $css => $css !== ''])>{{ $title }} <span class="cx-n">{{ $number }}</span></a>
                    @endif
                @endforeach
            </nav>
        @endif
    </div>
    @if(!$runs->count())
        <div class="cx-card-body cx-empty-note">
            @if($runsFilter === 'failed')
                No failed runs.
            @elseif($runsFilter === 'running')
                Nothing is running now.
            @else
                No runs yet.
            @endif
        </div>
    @endif
    @foreach($runs as $run)
        @php
            [$runState, $runStateTitle] = $states[$run->state] ?? [null, 'Unknown'];
            $runLogs = $logs[$run->id];
            // The latest run and the ones that went wrong are open
            $open = $loop->first || (in_array((int) $run->state, [1, 2, 3], true) && $loop->index < 5);
        @endphp
        <details class="cx-run" id="run-{{ $run->id }}" @if($open) open @endif>
            <summary class="cx-run-head">
                <span class="cx-dot {{ $runState }}"></span>
                <b class="cx-run-state {{ $runState }}">{{ $runStateTitle }}</b>
                <span class="cx-run-id">#{{ $run->id }}</span>
                <time title="{{ $run->created_at }}">{{ $run->created_at->diffForHumans() }}</time>
                <span class="cx-run-meta">
                    <span title="Duration">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        {{ $seconds($duration($run)) }}
                    </span>
                    @if($run->memory)
                        <span title="Memory">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="4" y="7" width="16" height="10" rx="2"/><path d="M8 17v2M12 17v2M16 17v2M8 5v2M12 5v2M16 5v2"/></svg>
                            {{ $run->memory }}
                        </span>
                    @endif
                    @if($run->user)
                        <span title="Run by">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                            {{ $run->user->email ?? $run->user->getKey() }}
                        </span>
                    @endif
                    @if($run->host)
                        <span title="Host">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="4" width="18" height="7" rx="1.5"/><rect x="3" y="13" width="18" height="7" rx="1.5"/><path d="M7 7.5h.01M7 16.5h.01"/></svg>
                            {{ $run->host }}
                        </span>
                    @endif
                </span>
                <svg class="cx-run-chevron" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                @if($run->args)
                    <code class="cx-code cx-run-args">{{ $command->getNameWithArguments($run->args) }}</code>
                @endif
            </summary>
            @if($runLogs->count())
                <div class="cx-logs">
                    @foreach($runLogs as $log)
                        <div class="cx-log {{ $log->type->css() }}">
                            @if($log->type->css())
                                <span class="cx-log-msg">{{ $log->message }}</span>
                            @else
                                <div class="cx-log-dump">{!! $log->message !!}</div>
                            @endif
                            <time title="{{ $log->created_at }}">{{ $log->created_at?->format('H:i:s') }}</time>
                        </div>
                    @endforeach
                    @if($runLogs->hasPages())
                        @php
                            $runLogs->appends(request()->query())->fragment('run-' . $run->id);
                        @endphp
                        <div class="cx-log-pages">
                            @unless($runLogs->onFirstPage())
                                <a class="cx-btn ghost small" href="{{ $runLogs->previousPageUrl() }}">‹ Earlier</a>
                            @endunless
                            <span>Page {{ $runLogs->currentPage() }}</span>
                            @if($runLogs->hasMorePages())
                                <a class="cx-btn ghost small" href="{{ $runLogs->nextPageUrl() }}">Later ›</a>
                            @endif
                        </div>
                    @endif
                </div>
            @else
                <div class="cx-logs"><span class="cx-none">No output</span></div>
            @endif
        </details>
    @endforeach
    @if($runs->hasPages())
        <div class="cx-card-foot">
            {{ $runs->appends(request()->query())->fragment('runs')->links('chronos::pagination') }}
        </div>
    @endif
</section>
