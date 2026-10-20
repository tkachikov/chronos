@extends('chronos::layout', ['title' => 'Commands'])
@php
    $sorted = (bool) request('sortKey');
    $groups = $sorted
        ? collect([null => collect($commands)])
        : collect($commands)->groupBy(fn ($command) => $command->getGroupName() ?? $command->getDirectory() ?? '');
    // Commands in the root of app/Console/Commands have no group
    $groupTitle = fn (string $name): string => $name !== '' ? $name : 'Application';
    $groupId = fn (string $name): string => \Illuminate\Support\Str::slug($groupTitle($name)) ?: 'commands';
    $isFailing = fn ($command): bool => in_array($command->getModel()->lastRun?->state, [1, 3], true);
    // Titles and hints of the options of the filters, for the filters and the empty state
    $filterLabels = [
        'lastRunState' => [
            'Success' => ['Succeeded', null],
            'Failed' => ['Failed', null],
            'Running' => ['Running', null],
            'Never run' => ['Never run', null],
        ],
        'runsIn' => [
            'Manual on' => ['Can run manually', 'It can be run from the panel'],
            'Manual off' => ['Can’t run manually', 'Running from the panel is off'],
            'Schedule on' => ['Can be scheduled', 'It can have schedules'],
            'Schedule off' => ['Can’t be scheduled', 'Schedules are off for it'],
        ],
        'schedulers' => [
            'Missing' => ['No schedules', 'Not scheduled at all'],
            'Has one' => ['One schedule', null],
            'Has many' => ['Several schedules', null],
            'Some off' => ['Some turned off', 'At least one schedule is off'],
            'All off' => ['All turned off', 'Every schedule is off'],
        ],
    ];
    $schedulesOn = collect($commands)->sum(fn ($command) => $command->getModel()->schedules->where('run', true)->count());
    $failing = collect($commands)->filter($isFailing)->count();
    // The open group, all of them when it is not chosen or not found (e.g. after a search)
    $selectedGroup = !$sorted && $groups->keys()->contains(fn ($name) => $groupId((string) $name) === request('group'))
        ? (string) request('group')
        : '';
    $running = collect($commands)->filter(fn ($command) => $command->getModel()->lastRun?->state === 2)->count();
    $states = [
        0 => ['success', 'Succeeded'],
        1 => ['failed', 'Failed'],
        2 => ['running', 'Running'],
        3 => ['killed', 'Killed'],
    ];
    $seconds = function ($value): string {
        if ($value === null || $value === '') {
            return '—';
        }
        $value = (int) round((float) $value);

        return match (true) {
            $value < 60 => $value . 's',
            $value < 3600 => intdiv($value, 60) . 'm ' . str_pad((string) ($value % 60), 2, '0', STR_PAD_LEFT) . 's',
            default => intdiv($value, 3600) . 'h ' . str_pad((string) intdiv($value % 3600, 60), 2, '0', STR_PAD_LEFT) . 'm',
        };
    };
@endphp

@section('styles')
    <style>
        .cx-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px 24px; flex-wrap: wrap; margin-bottom: 16px; }
        .cx-head h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -.01em; }
        .cx-stats { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
        .cx-stat { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: var(--cx-muted); background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 999px; padding: 3px 10px; }
        .cx-stat b { color: var(--cx-text); font-weight: 600; }
        .cx-stat.bad { color: var(--cx-danger); background: var(--cx-danger-soft); border-color: transparent; }
        .cx-stat.bad b { color: var(--cx-danger); }
        .cx-stat.active { color: var(--cx-info); background: var(--cx-primary-soft); border-color: transparent; }
        .cx-stat.active b { color: var(--cx-info); }

        .cx-search { display: flex; align-items: center; gap: 8px; width: 380px; max-width: 100%; height: 38px; padding: 0 10px 0 12px; background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 10px; color: var(--cx-faint); margin: 0; cursor: text; }
        .cx-search:focus-within { border-color: var(--cx-primary); box-shadow: 0 0 0 3px var(--cx-primary-soft); }
        .cx-search input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; color: var(--cx-text); font-size: 14px; }
        .cx-search kbd { background: var(--cx-surface-2); color: var(--cx-muted); border: 1px solid var(--cx-border); font-size: 11px; padding: 1px 6px; box-shadow: none; }

        /* The tools: the last run as tabs, the other filters and the sort as quiet menus on one line */
        .cx-tools { display: flex; align-items: flex-end; gap: 16px; border-bottom: 1px solid var(--cx-border); margin-bottom: 20px; }
        .cx-tabs { display: flex; gap: 2px; flex: 1; min-width: 0; overflow-x: auto; scrollbar-width: none; }
        .cx-tabs::-webkit-scrollbar { display: none; }
        .cx-tabs a { display: inline-flex; align-items: center; gap: 7px; flex: none; padding: 10px 12px; margin-bottom: -1px; border-bottom: 2px solid transparent; color: var(--cx-muted); font-size: 14px; font-weight: 500; text-decoration: none; white-space: nowrap; }
        .cx-tabs a:hover { color: var(--cx-text); }
        .cx-tabs a.active { color: var(--cx-primary); border-bottom-color: var(--cx-primary); font-weight: 600; }
        .cx-tabs a.zero { color: var(--cx-faint); }
        .cx-tabs .cx-n { color: var(--cx-faint); background: transparent; padding: 0; min-width: 0; }
        .cx-tabs a.active .cx-n { color: var(--cx-primary); }
        .cx-seg-dot { width: 8px; height: 8px; border-radius: 50%; }
        .cx-seg-dot.success { background: var(--cx-success); }
        .cx-seg-dot.failed { background: var(--cx-danger); }
        .cx-seg-dot.running { background: var(--cx-info); }
        .cx-seg-dot.never-run { box-shadow: inset 0 0 0 2px var(--cx-faint); }
        .cx-tabs a.zero .cx-seg-dot { opacity: .5; }

        .cx-tools-end { display: flex; align-items: center; gap: 2px; padding-bottom: 6px; }
        .cx-tools-toggle { display: none; }
        .cx-facets { display: flex; gap: 2px; }
        .cx-facets.collapse:not(.show) { display: flex; }
        .cx-facet-button { display: inline-flex; align-items: center; gap: 5px; height: 32px; padding: 0 8px 0 10px; border: 0; border-radius: 8px; background: transparent; color: var(--cx-muted); font-size: 13px; font-weight: 500; white-space: nowrap; }
        .cx-facet-button:hover, .cx-facet-button[aria-expanded="true"] { color: var(--cx-text); background: var(--cx-surface); }
        .cx-facet.active .cx-facet-button { color: var(--cx-primary); background: var(--cx-primary-soft); font-weight: 600; }
        .cx-sort { margin-left: 6px; }
        .cx-facet-button b { font-weight: 600; }
        .cx-sort-menu { min-width: 240px; }
        .cx-sort-row { display: flex; align-items: center; gap: 2px; padding: 3px 6px 3px 10px; font-size: 14px; color: var(--cx-text); }
        .cx-sort-row span { flex: 1; }
        .cx-sort-row a { display: grid; place-items: center; width: 28px; height: 28px; border-radius: 6px; color: var(--cx-faint); text-decoration: none; font-size: 15px; }
        .cx-sort-row a:hover { color: var(--cx-text); background: var(--cx-surface-2); }
        .cx-sort-row a.active { color: var(--cx-primary); background: var(--cx-primary-soft); }

        .cx-menu { min-width: 260px; max-height: 340px; overflow-y: auto; padding: 6px; border-radius: 12px; border-color: var(--cx-border); background: var(--cx-surface); box-shadow: var(--cx-shadow-hover); font-size: 14px; }
        .cx-menu .dropdown-item { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 7px 10px; border-radius: 8px; color: var(--cx-text); white-space: normal; }
        .cx-menu .dropdown-item:hover { background: var(--cx-surface-2); }
        .cx-menu .dropdown-item.active { background: var(--cx-primary-soft); color: var(--cx-primary); font-weight: 600; }
        .cx-menu .dropdown-item small { display: block; color: var(--cx-muted); font-size: 12px; font-weight: 400; }
        .cx-menu .dropdown-item.zero, .cx-menu .dropdown-item.zero small { color: var(--cx-faint); }
        .cx-menu-title { padding: 6px 10px 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--cx-faint); }
        .cx-menu-group { padding: 8px 10px 2px; font-size: 12px; font-weight: 600; color: var(--cx-muted); }
        .cx-menu-search { padding: 4px 4px 6px; }
        .cx-menu-search input { width: 100%; height: 32px; padding: 0 10px; border: 1px solid var(--cx-border); border-radius: 8px; background: var(--cx-surface-2); color: var(--cx-text); font-size: 13px; outline: 0; }
        .cx-menu-search input:focus { border-color: var(--cx-primary); box-shadow: 0 0 0 3px var(--cx-primary-soft); }
        .cx-menu-empty { padding: 8px 10px; color: var(--cx-muted); font-size: 13px; max-width: 260px; }
        /* How many commands an option gives with the other filters */
        .cx-n { font-size: 11px; font-weight: 600; min-width: 20px; padding: 0 6px; border-radius: 999px; text-align: center; color: var(--cx-muted); background: var(--cx-surface-2); }
        .cx-menu .dropdown-item.active .cx-n { color: var(--cx-primary); background: var(--cx-surface); }

        /* What is applied */
        .cx-applied { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin: -8px 0 20px; font-size: 13px; color: var(--cx-muted); }
        .cx-chip { display: inline-flex; align-items: center; gap: 6px; height: 28px; padding: 0 8px 0 10px; border-radius: 999px; background: var(--cx-primary-soft); color: var(--cx-primary); text-decoration: none; }
        .cx-chip b { font-weight: 600; }
        .cx-chip:hover { color: var(--cx-danger); background: var(--cx-danger-soft); }
        .cx-applied-end { display: inline-flex; align-items: center; gap: 10px; margin-left: 4px; }
        .cx-result b { color: var(--cx-text); }
        .cx-clear { color: var(--cx-muted); }
        .cx-clear:hover { color: var(--cx-danger); }

        .cx-body { display: grid; grid-template-columns: 220px minmax(0, 1fr); gap: 28px; align-items: start; }
        .cx-body.flat { grid-template-columns: minmax(0, 1fr); }

        /* Groups on the left, the current one is highlighted while scrolling */
        .cx-side { position: sticky; top: 84px; display: flex; flex-direction: column; gap: 2px; margin-top: 28px; }
        .cx-side-title { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--cx-faint); padding: 0 12px 8px; }
        .cx-side a { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 10px; color: var(--cx-muted); text-decoration: none; font-size: 14px; font-weight: 500; border-left: 3px solid transparent; }
        .cx-side a:hover { color: var(--cx-text); background: var(--cx-surface); }
        .cx-side a.active { color: var(--cx-primary); background: var(--cx-primary-soft); border-left-color: var(--cx-primary); }
        .cx-side a span:first-child { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .cx-side-count { font-size: 12px; color: var(--cx-faint); }
        .cx-side a.active .cx-side-count { color: var(--cx-primary); }
        .cx-side a.all { margin-bottom: 6px; }
        .cx-side-sep { height: 1px; background: var(--cx-border); margin: 0 12px 6px; }

        /* A group appears softly when it is opened */
        .cx-group.cx-enter { animation: cx-enter .22s ease-out; }
        @keyframes cx-enter { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        .cx-side-bad { display: inline-grid; place-items: center; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px; background: var(--cx-danger-soft); color: var(--cx-danger); font-size: 11px; font-weight: 700; }

        .cx-group { margin-top: 28px; scroll-margin-top: 84px; }
        .cx-group-head { display: flex; align-items: center; gap: 10px; margin: 0 4px 10px; }
        .cx-group-head h2 { margin: 0; font-size: 15px; font-weight: 600; color: var(--cx-text); }
        .cx-group-head span { font-size: 12px; color: var(--cx-muted); background: var(--cx-surface-2); border: 1px solid var(--cx-border); border-radius: 999px; padding: 0 8px; }

        .cx-list { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 14px; box-shadow: var(--cx-shadow); overflow: hidden; }
        .cx-cols, .cx-row { display: grid; grid-template-columns: minmax(0, 2.3fr) minmax(0, 1.7fr) minmax(0, 1fr) minmax(0, 1.1fr) 28px; gap: 20px; align-items: center; padding: 14px 20px; }
        .cx-cols { padding-top: 10px; padding-bottom: 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--cx-faint); background: var(--cx-surface-2); border-bottom: 1px solid var(--cx-border); }
        .cx-row { position: relative; border-top: 1px solid var(--cx-border); transition: background .12s; }
        .cx-cols + .cx-row { border-top: 0; }
        .cx-row:hover { background: var(--cx-surface-2); }

        .cx-command { display: flex; gap: 12px; align-items: flex-start; min-width: 0; }
        .cx-command .cx-dot { margin-top: 6px; }

        .cx-title { display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap; }
        /* The whole row opens the command */
        .cx-name { font-weight: 600; color: var(--cx-text); text-decoration: none; }
        .cx-name::after { content: ""; position: absolute; inset: 0; }
        .cx-row:hover .cx-name { color: var(--cx-primary); }
        .cx-sig { font-size: 12px; color: var(--cx-muted); background: var(--cx-surface-2); border: 1px solid var(--cx-border); border-radius: 6px; padding: 1px 6px; }
        .cx-desc { margin-top: 2px; font-size: 13px; color: var(--cx-muted); overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .cx-tags { display: flex; gap: 6px; margin-top: 6px; flex-wrap: wrap; }

        .cx-schedules { display: flex; flex-direction: column; gap: 6px; min-width: 0; font-size: 13px; }
        .cx-schedule { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
        .cx-schedule .badge { position: relative; z-index: 1; }
        .cx-schedule.off { color: var(--cx-faint); }

        .cx-last { font-size: 13px; }
        .cx-last b { font-weight: 600; }
        .cx-last .success { color: var(--cx-success); }
        .cx-last .failed, .cx-last .killed { color: var(--cx-danger); }
        .cx-last .running { color: var(--cx-info); }
        .cx-last small { display: block; color: var(--cx-muted); }

        .cx-metrics { display: grid; grid-template-columns: auto 1fr; gap: 2px 10px; font-size: 13px; }
        .cx-metrics span { color: var(--cx-faint); }
        .cx-metrics small { color: var(--cx-muted); margin-left: 4px; }

        .cx-open { color: var(--cx-faint); line-height: 0; }
        .cx-row:hover .cx-open { color: var(--cx-primary); transform: translateX(2px); transition: transform .12s; }

        .cx-empty { padding: 56px 20px; text-align: center; color: var(--cx-muted); }
        .cx-empty b { display: block; color: var(--cx-text); font-size: 16px; margin-bottom: 4px; }
        .cx-suggestions { display: flex; justify-content: center; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .cx-suggestions a { font-size: 13px; color: var(--cx-primary); background: var(--cx-primary-soft); border-radius: 999px; padding: 4px 12px; text-decoration: none; }
        .cx-suggestions a:hover { color: var(--cx-danger); background: var(--cx-danger-soft); }
        .cx-suggestions a.all { color: var(--cx-muted); background: transparent; border: 1px solid var(--cx-border); }

        @media (max-width: 1100px) {
            .cx-tools { flex-wrap: wrap; gap: 0; border-bottom: 0; }
            .cx-tabs { flex-basis: 100%; border-bottom: 1px solid var(--cx-border); }
            .cx-tools-end { padding: 8px 0 0; margin-left: -10px; }
            .cx-applied { margin-top: 4px; }
            .cx-body { grid-template-columns: minmax(0, 1fr); gap: 0; }
            .cx-side { position: static; flex-direction: row; overflow-x: auto; margin-top: 20px; }
            .cx-side-title { display: none; }
            .cx-side a { border-left: 0; flex: none; }
            .cx-cols { display: none; }
            .cx-row { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 12px 20px; }
            .cx-command { grid-column: 1 / -1; }
            .cx-open { display: none; }
        }

        @media (max-width: 768px) {
            .cx-tools { flex-wrap: wrap; gap: 0; border-bottom: 0; margin-bottom: 12px; }
            .cx-tabs { margin: 0 -16px; padding: 0 8px; }
            .cx-tabs a { padding: 10px; font-size: 13px; }
            .cx-tools-end { flex-basis: 100%; flex-wrap: wrap; gap: 8px; padding: 12px 0 0; margin-left: 0; }
            .cx-tools-toggle { display: inline-flex; align-items: center; justify-content: center; gap: 8px; flex: 1; height: 38px; padding: 0 12px; border: 1px solid var(--cx-border); border-radius: 10px; background: var(--cx-surface); color: var(--cx-text); font-size: 14px; font-weight: 600; }
            .cx-tools-toggle[aria-expanded="true"] { border-color: var(--cx-primary); color: var(--cx-primary); }
            .cx-tools-toggle .cx-n { color: var(--cx-primary); background: var(--cx-primary-soft); }
            .cx-sort { flex: 1; margin: 0; display: flex; }
            .cx-sort .cx-facet-button { flex: 1; justify-content: space-between; height: 38px; padding: 0 12px; border: 1px solid var(--cx-border); border-radius: 10px; background: var(--cx-surface); color: var(--cx-text); font-size: 14px; font-weight: 600; }
            .cx-sort.active .cx-facet-button { border-color: var(--cx-primary); color: var(--cx-primary); }
            .cx-sort .cx-facet-button b { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .cx-facets { flex-basis: 100%; order: 10; display: block; }
            .cx-facets.collapse:not(.show) { display: none; }
            .cx-facets .cx-facet { margin-top: 14px; }
            .cx-facets .cx-facet-button { display: none; }
            .cx-facets .cx-menu { display: flex !important; position: static !important; transform: none !important; flex-wrap: wrap; gap: 6px; min-width: 0; max-height: none; padding: 0; border: 0; border-radius: 0; background: transparent; box-shadow: none; }
            .cx-facets .cx-menu li { display: contents; }
            .cx-facets .cx-menu li.cx-menu-title { display: block; flex-basis: 100%; padding: 0 0 2px; }
            .cx-facets .cx-menu li.cx-menu-group, .cx-facets .cx-menu li.cx-menu-search, .cx-facets .cx-menu li.cx-menu-empty[data-method-nothing] { display: none; }
            .cx-facets .cx-menu li.cx-menu-empty { display: block; padding: 0; }
            .cx-facets .cx-menu .dropdown-item { display: inline-flex; width: auto; gap: 6px; height: 30px; padding: 0 10px; border: 1px solid var(--cx-border); border-radius: 999px; background: var(--cx-surface); font-size: 13px; }
            .cx-facets .cx-menu .dropdown-item.active { border-color: transparent; }
            .cx-facets .cx-menu .dropdown-item small { display: none; }
            .cx-facets .cx-menu .dropdown-item .cx-n { padding: 0; min-width: 0; background: transparent; color: var(--cx-faint); }
            .cx-facets .cx-menu .dropdown-item.active .cx-n { color: var(--cx-primary); }
            .cx-applied { margin: 0 0 16px; }
            .cx-applied-end { flex-basis: 100%; margin: 2px 0 0; }
        }

        @media (max-width: 640px) {
            .cx-head { margin-bottom: 16px; gap: 12px; }
            .cx-head h1 { font-size: 24px; }
            .cx-row { grid-template-columns: minmax(0, 1fr); gap: 10px; padding: 14px 16px; }
            .cx-search { width: 100%; }
            .cx-search kbd { display: none; }
            /* The groups scroll from edge to edge */
            .cx-side { overflow-x: auto; margin-left: -16px; margin-right: -16px; padding: 0 16px; scrollbar-width: none; }
            .cx-side::-webkit-scrollbar { display: none; }
            .cx-group { margin-top: 20px; }
        }
    </style>
@endsection

@section('content')
    <div class="cx-head">
        <div>
            <h1>Commands</h1>
            <div class="cx-stats">
                <span class="cx-stat"><b>{{ count($commands) }}</b> commands</span>
                <span class="cx-stat"><b>{{ $schedulesOn }}</b> schedules on</span>
                @if($running)
                    <span class="cx-stat active"><b>{{ $running }}</b> running</span>
                @endif
                @if($failing)
                    <a class="cx-stat bad text-decoration-none" href="{{ route('chronos.main', ['lastRunState' => \Tkachikov\Chronos\Enums\LastRunStateFilterEnum::FAILED->value]) }}"><b>{{ $failing }}</b> failing</a>
                @endif
            </div>
        </div>
        <form class="cx-search" method="GET" action="{{ route('chronos.main') }}" role="search">
            {{-- The filters, the sort and the group are kept by the search --}}
            @foreach(['runsIn', 'scheduleMethod', 'schedulers', 'lastRunState', 'sortKey', 'sortBy', 'group'] as $key)
                @if(request($key))
                    <input type="hidden" name="{{ $key }}" value="{{ request($key) }}">
                @endif
            @endforeach
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="Search by name, signature or description" autocomplete="off">
            <kbd>/</kbd>
        </form>
    </div>
    @include('chronos::filters', ['times' => $times, 'labels' => $filterLabels])

    <div @class(['cx-body', 'flat' => $sorted || !count($commands)])>
    @if(!$sorted && count($commands))
        <nav class="cx-side" aria-label="Groups">
            <div class="cx-side-title">Groups</div>
            <a href="{{ route('chronos.main', request()->except('group')) }}" data-group-link="" @class(['all', 'active' => $selectedGroup === '']) @if($selectedGroup === '') aria-current="true" @endif>
                <span>All commands</span>
                @if($failing)
                    <span class="cx-side-bad" title="{{ $failing }} failing">{{ $failing }}</span>
                @endif
                <span class="cx-side-count">{{ count($commands) }}</span>
            </a>
            <div class="cx-side-sep"></div>
            @foreach($groups as $groupName => $items)
                @php
                    $id = $groupId((string) $groupName);
                    $bad = $items->filter($isFailing)->count();
                @endphp
                <a href="{{ route('chronos.main', ['group' => $id] + request()->except('group')) }}" data-group-link="{{ $id }}" @class(['active' => $selectedGroup === $id]) @if($selectedGroup === $id) aria-current="true" @endif>
                    <span>{{ $groupTitle((string) $groupName) }}</span>
                    @if($bad)
                        <span class="cx-side-bad" title="{{ $bad }} failing">{{ $bad }}</span>
                    @endif
                    <span class="cx-side-count">{{ $items->count() }}</span>
                </a>
            @endforeach
        </nav>
    @endif
    <div>
    @forelse($groups as $groupName => $items)
        <section class="cx-group" data-group="{{ $groupId((string) $groupName) }}" @if($selectedGroup !== '' && $selectedGroup !== $groupId((string) $groupName)) hidden @endif>
            @if(!$sorted)
                <div class="cx-group-head">
                    <h2>{{ $groupTitle((string) $groupName) }}</h2>
                    <span>{{ $items->count() }}</span>
                </div>
            @endif
            <div class="cx-list">
                <div class="cx-cols">
                    <div>Command</div>
                    <div>Schedules</div>
                    <div>Last run</div>
                    <div>Time · Memory</div>
                    <div></div>
                </div>
                @foreach($items as $command)
                    @php
                        $model = $command->getModel();
                        $lastRun = $model->lastRun;
                        [$state, $stateTitle] = $states[$lastRun?->state] ?? [null, 'Never run'];
                        $metrics = $model->metrics;
                    @endphp
                    <div class="cx-row">
                        <div class="cx-command">
                            <span class="cx-dot {{ $state }}" title="Last run: {{ $stateTitle }}"></span>
                            <div style="min-width: 0">
                                <div class="cx-title">
                                    <a class="cx-name" href="{{ route('chronos.edit', $model) }}">{{ $command->getShortName() }}</a>
                                    <code class="cx-sig">{{ $command->getName() }}</code>
                                </div>
                                @if($command->getDescription())
                                    <div class="cx-desc" title="{{ $command->getDescription() }}">{{ $command->getDescription() }}</div>
                                @endif
                                @if($sorted || $command->notRunInManual() || $command->notRunInSchedule())
                                    <div class="cx-tags">
                                        @if($sorted && ($command->getGroupName() ?? $command->getDirectory()))
                                            <span class="cx-tag">{{ $command->getGroupName() ?? $command->getDirectory() }}</span>
                                        @endif
                                        @if($command->notRunInManual())
                                            <span class="cx-tag off" title="It can not be run from the panel">Manual off</span>
                                        @endif
                                        @if($command->notRunInSchedule())
                                            <span class="cx-tag off" title="It can not be scheduled">Schedule off</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="cx-schedules">
                            @forelse($model->schedules as $schedule)
                                <div @class(['cx-schedule', 'off' => !$schedule->run])>
                                    <span @class(['cx-switch', 'off' => !$schedule->run]) title="{{ $schedule->run ? 'On' : 'Off' }}"></span>
                                    <span>
                                        @if($schedule->name)
                                            <b>{{ $schedule->name }}</b> ·
                                        @endif
                                        {{ $times[$schedule->time_method]->getDescription($schedule) }}
                                    </span>
                                    @include('chronos::monitoring.last-run', ['item' => $schedule, 'compact' => true])
                                </div>
                            @empty
                                <span class="cx-none">No schedules</span>
                            @endforelse
                        </div>

                        <div class="cx-last">
                            @if($lastRun)
                                <b class="{{ $state }}">{{ $stateTitle }}</b>
                                <small title="{{ $lastRun->created_at }}">{{ $lastRun->created_at?->diffForHumans() }}</small>
                            @else
                                <span class="cx-none">Never run</span>
                            @endif
                        </div>

                        <div class="cx-metrics">
                            @if($metrics)
                                <span>Time</span>
                                <div title="Average, min–max">
                                    {{ $seconds($metrics->time_avg) }}
                                    @if($metrics->time_min != $metrics->time_max)
                                        <small>{{ $seconds($metrics->time_min) }}–{{ $seconds($metrics->time_max) }}</small>
                                    @endif
                                </div>
                                <span>Memory</span>
                                <div title="Average, min–max">
                                    {{ $metrics->memory_avg ?? '—' }}
                                    @if($metrics->memory_min !== $metrics->memory_max)
                                        <small>{{ $metrics->memory_min }}–{{ $metrics->memory_max }}</small>
                                    @endif
                                </div>
                            @else
                                <span class="cx-none" style="grid-column: 1 / -1">No statistics yet</span>
                            @endif
                        </div>

                        <div class="cx-open" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        @php
            // Each active filter can be removed alone: maybe only one of them hides the commands
            $label = fn (string $key): ?string => $filterLabels[$key][(string) request($key)][0] ?? null;
            $suggestions = array_filter([
                'search' => request('search') ? '“' . request('search') . '”' : null,
                'lastRunState' => $label('lastRunState'),
                'runsIn' => $label('runsIn'),
                'schedulers' => $label('schedulers'),
                'scheduleMethod' => isset($times[(string) request('scheduleMethod')]) ? $times[(string) request('scheduleMethod')]->getTitle() : null,
            ]);
        @endphp
        <div class="cx-list cx-empty">
            <b>No commands found</b>
            @if($suggestions)
                Nothing matches all the filters. Try without:
                <div class="cx-suggestions">
                    @foreach($suggestions as $key => $title)
                        <a href="{{ route('chronos.main', request()->except($key)) }}">{{ $title }} ×</a>
                    @endforeach
                    <a class="all" href="{{ route('chronos.main', request()->only('sortKey', 'sortBy', 'group')) }}">all filters</a>
                </div>
            @else
                There are no commands yet.
            @endif
        </div>
    @endforelse
    </div>
    </div>
@endsection

@section('footer_scripts')
    <script>
        // "/" focuses the search, the sort applies at once
        document.addEventListener('keydown', (event) => {
            if (event.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
                event.preventDefault();
                document.getElementById('search').focus();
            }
        });

        // A group opens only its commands, "All commands" opens all of them, without reloading the page
        const links = document.querySelectorAll('[data-group-link]');
        const sections = document.querySelectorAll('.cx-group[data-group]');

        const openGroup = (group) => {
            sections.forEach((section) => {
                const shown = !group || section.dataset.group === group;

                if (shown && section.hidden) {
                    section.classList.remove('cx-enter');
                    void section.offsetWidth;
                    section.classList.add('cx-enter');
                }

                section.hidden = !shown;
            });

            links.forEach((link) => {
                const active = link.dataset.groupLink === group;
                link.classList.toggle('active', active);
                active ? link.setAttribute('aria-current', 'true') : link.removeAttribute('aria-current');
            });

            // Back to the top of the list when it is scrolled below
            const body = document.querySelector('.cx-body');
            if (body && body.getBoundingClientRect().top < 0) {
                window.scrollTo({ top: window.scrollY + body.getBoundingClientRect().top - 84, behavior: 'smooth' });
            }
        };

        links.forEach((link) => link.addEventListener('click', (event) => {
            if (event.metaKey || event.ctrlKey || event.shiftKey) {
                return;
            }

            event.preventDefault();
            history.pushState(null, '', link.href);
            openGroup(link.dataset.groupLink);
        }));

        window.addEventListener('popstate', () => openGroup(new URL(location.href).searchParams.get('group') || ''));

        // The search of frequencies: by the title, groups without matches are hidden
        const methodSearch = document.querySelector('[data-method-search]');

        if (methodSearch) {
            const menu = methodSearch.closest('[data-method-menu]');
            methodSearch.addEventListener('input', () => {
                const query = methodSearch.value.trim().toLowerCase();
                let found = 0;
                let group = null;
                let groupFound = 0;
                const closeGroup = () => group && (group.hidden = groupFound === 0);

                menu.querySelectorAll('[data-method-group], [data-method-item]').forEach((item) => {
                    if (item.matches('[data-method-group]')) {
                        closeGroup();
                        group = item;
                        groupFound = 0;
                        return;
                    }

                    const shown = item.textContent.toLowerCase().includes(query);
                    item.hidden = !shown;
                    groupFound += shown;
                    found += shown;
                });

                closeGroup();
                menu.querySelector('[data-method-nothing]').hidden = found > 0;
            });
            menu.closest('.dropdown').addEventListener('shown.bs.dropdown', () => methodSearch.focus());
        }
    </script>
@endsection
