@php
    use Tkachikov\Chronos\Enums\RunsInFilterEnum;
    use Tkachikov\Chronos\Enums\SchedulersFilterEnum;

    // The current page with one filter changed, an empty value removes it
    $url = fn (string $key, ?string $value = null): string => route('chronos.main', array_filter(
        [...request()->except($key), $key => $value],
        fn ($item) => $item !== null && $item !== '',
    ));
    $count = fn (string $key, string $value): int => $facets[$key][$value] ?? 0;

    $lastRuns = ['' => 'All'] + array_map(fn (array $label): string => $label[0], $labels['lastRunState']);

    // Methods in use by schedules (with the other filters), grouped by their period
    $methodGroup = function (string $method): string {
        $method = strtolower($method);

        return match (true) {
            $method === 'cron' => 'Custom',
            str_contains($method, 'second') => 'Seconds',
            str_contains($method, 'minute') => 'Minutes',
            str_contains($method, 'hour') => 'Hours',
            str_contains($method, 'daily') => 'Days',
            str_contains($method, 'week') => 'Weeks',
            str_contains($method, 'month') => 'Months',
            str_contains($method, 'quarter') => 'Quarters',
            str_contains($method, 'year') => 'Years',
            default => 'Other',
        };
    };
    $methods = collect($times)
        ->filter(fn ($time, $method) => $count('scheduleMethod', $method) > 0 || request('scheduleMethod') === $method)
        ->map(fn ($time, $method) => ['title' => $time->getTitle(), 'group' => $methodGroup($method)]);

    // The filters as menus: the key of the query, the title, the options as value => [title, hint]
    $menus = [
        'runsIn' => ['title' => 'Runs in', 'options' => $labels['runsIn']],
        'schedulers' => ['title' => 'Schedules', 'options' => $labels['schedulers']],
        'scheduleMethod' => ['title' => 'Frequency', 'options' => $methods->map(fn ($item) => [$item['title'], null])->all()],
    ];
    $chosen = fn (string $key): string => isset($menus[$key]['options'][(string) request($key)]) ? (string) request($key) : '';
    // The values in use, "All" of a menu is not a filter
    $applied = collect($menus)
        ->map(fn ($menu, $key) => $chosen($key))
        ->filter(fn ($value) => $value !== '' && !in_array($value, [RunsInFilterEnum::ALL->value, SchedulersFilterEnum::ALL->value], true));
    $filtered = $applied->isNotEmpty() || request('search');

    $sorts = [
        'time_avg' => 'Time, average',
        'time_max' => 'Time, max',
        'time_min' => 'Time, min',
        'memory_avg' => 'Memory, average',
        'memory_min' => 'Memory, min',
        'memory_max' => 'Memory, max',
    ];
@endphp

<div class="cx-tools">
    {{-- The last run as tabs --}}
    <nav class="cx-tabs" aria-label="Last run">
        @foreach($lastRuns as $value => $title)
            @php
                $selected = (string) request('lastRunState') === (string) $value;
                $number = $value === '' ? ($facets['total'] ?? count($commands)) : $count('lastRunState', (string) $value);
            @endphp
            <a href="{{ $url('lastRunState', (string) $value) }}" @class(['active' => $selected, 'zero' => $number === 0 && !$selected]) @if($selected) aria-current="true" @endif>
                @if($value !== '')
                    <i class="cx-seg-dot {{ \Illuminate\Support\Str::slug($value) }}"></i>
                @endif
                {{ $title }}
                <span class="cx-n">{{ $number }}</span>
            </a>
        @endforeach
    </nav>

    <div class="cx-tools-end">
        {{-- On a phone the menus are rows of chips under this button --}}
        <button class="cx-tools-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#cx-facets" aria-expanded="false" aria-controls="cx-facets">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
            Filters
            @if($applied->isNotEmpty())
                <span class="cx-n">{{ $applied->count() }}</span>
            @endif
        </button>

        <div class="cx-facets collapse" id="cx-facets">
            @foreach($menus as $key => $menu)
                @php
                    $current = $chosen($key);
                    $active = $applied->has($key);
                    $isMethods = $key === 'scheduleMethod';
                @endphp
                <div @class(['dropdown', 'cx-facet', 'active' => $active])>
                    <button class="cx-facet-button" type="button" data-bs-toggle="dropdown" @if($isMethods) data-bs-auto-close="outside" @endif aria-expanded="false">
                        {{ $menu['title'] }}
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <ul class="dropdown-menu cx-menu" @if($isMethods) data-method-menu @endif>
                        <li class="cx-menu-title">{{ $isMethods ? 'Frequency of schedules' : $menu['title'] }}</li>
                        @if($isMethods && $methods->count() > 6)
                            <li class="cx-menu-search"><input type="search" placeholder="Find a frequency" aria-label="Find a frequency" data-method-search></li>
                        @endif
                        <li><a @class(['dropdown-item', 'active' => !$active]) href="{{ $url($key) }}"><span>Any</span></a></li>
                        @if($isMethods)
                            @forelse($methods->groupBy('group', true) as $group => $items)
                                <li class="cx-menu-group" data-method-group>{{ $group }}</li>
                                @foreach($items as $method => $item)
                                    @php
                                        $number = $count('scheduleMethod', $method);
                                    @endphp
                                    <li data-method-item>
                                        <a @class(['dropdown-item', 'active' => $current === $method, 'zero' => $number === 0]) href="{{ $url('scheduleMethod', $method) }}">
                                            <span>{{ $item['title'] }}</span>
                                            <span class="cx-n">{{ $number }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            @empty
                                <li class="cx-menu-empty">No schedules yet: add one on the page of a command.</li>
                            @endforelse
                            <li class="cx-menu-empty" data-method-nothing hidden>Nothing found</li>
                        @else
                            @foreach($menu['options'] as $value => [$title, $hint])
                                @php
                                    $number = $count($key, (string) $value);
                                @endphp
                                <li>
                                    <a @class(['dropdown-item', 'active' => $current === (string) $value, 'zero' => $number === 0]) href="{{ $url($key, (string) $value) }}" @if($hint) title="{{ $hint }}" @endif>
                                        <span>
                                            {{ $title }}
                                            @if($hint)
                                                <small>{{ $hint }}</small>
                                            @endif
                                        </span>
                                        <span class="cx-n">{{ $number }}</span>
                                    </a>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            @endforeach
        </div>

        @php
            $sortKey = (string) request('sortKey');
            $sortActive = $sortKey !== '' && isset($sorts[$sortKey]);
            $sortBy = request('sortBy') === 'desc' ? 'desc' : 'asc';
            $sortUrl = fn (string $key, string $direction): string => route('chronos.main', [...request()->except('sortKey', 'sortBy'), 'sortKey' => $key, 'sortBy' => $direction]);
        @endphp
        <div @class(['dropdown', 'cx-facet', 'cx-sort', 'active' => $sortActive])>
            <button class="cx-facet-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Sort">
                @if($sortActive)
                    Sort: <b>{{ $sorts[$sortKey] }} {{ $sortBy === 'desc' ? '↓' : '↑' }}</b>
                @else
                    Sort: by groups
                @endif
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <ul class="dropdown-menu dropdown-menu-end cx-menu cx-sort-menu">
                <li class="cx-menu-title">Sort</li>
                <li><a @class(['dropdown-item', 'active' => !$sortActive]) href="{{ route('chronos.main', request()->except('sortKey', 'sortBy')) }}"><span>By groups</span></a></li>
                @foreach($sorts as $key => $title)
                    <li class="cx-sort-row">
                        <span>{{ $title }}</span>
                        <a @class(['active' => $sortActive && $sortKey === $key && $sortBy === 'desc']) href="{{ $sortUrl($key, 'desc') }}" title="{{ $title }}, descending" aria-label="{{ $title }}, descending">↓</a>
                        <a @class(['active' => $sortActive && $sortKey === $key && $sortBy === 'asc']) href="{{ $sortUrl($key, 'asc') }}" title="{{ $title }}, ascending" aria-label="{{ $title }}, ascending">↑</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

@if($filtered)
    {{-- What is applied: a chip removes its filter --}}
    <div class="cx-applied">
        @if(request('search'))
            <a class="cx-chip" href="{{ $url('search') }}" title="Remove the search">
                <b>“{{ request('search') }}”</b>
                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </a>
        @endif
        @foreach($applied as $key => $value)
            <a class="cx-chip" href="{{ $url($key) }}" title="Remove the filter {{ $menus[$key]['title'] }}">
                <b>{{ $menus[$key]['options'][$value][0] }}</b>
                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </a>
        @endforeach
        <span class="cx-applied-end">
            <span class="cx-result">Showing <b>{{ count($commands) }}</b> of {{ $facets['total'] ?? count($commands) }}</span>
            <a class="cx-clear" href="{{ route('chronos.main', request()->only('sortKey', 'sortBy', 'group')) }}">Clear all</a>
        </span>
    </div>
@endif
