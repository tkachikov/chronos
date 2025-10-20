@extends('chronos::layout', ['title' => 'test'])
@section('content')
    <div class="d-flex flex-row mb-3">
        <a class="btn btn-link text-decoration-none" href="/">
            <h1 class="h1 m-0">{{ config('app.name') }}</h1>
        </a>
        <span class="h4 m-0 py-3 text-muted">/</span>
        <a class="btn btn-link text-decoration-none" href="{{ route('chronos.main') }}">
            <h1 class="h1 m-0">Commands</h1>
        </a>
    </div>

    @include('chronos::filters', ['times' => $times])

    <div class="row w-100 mx-auto">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <table class="table m-0">
                        <thead>
                        <tr>
                            <th colspan="6" class="border-bottom-0"></th>
                            <th colspan="3" class="text-center">Time</th>
                            <th colspan="3" class="text-center">Memory</th>
                        </tr>
                        <tr>
                            @if(request('sortKey'))
                                <th>Group name</th>
                            @endif
                            <th>Command</th>
                            <th>Description</th>
                            <th>Runs in</th>
                            <th>Schedulers</th>
                            <th>Last run</th>
                            @foreach(['time', 'memory'] as $type)
                                @foreach(['avg', 'min', 'max'] as $key)
                                    @php
                                        $sortKey = $type.'_'.$key;
                                        $sortBy = (request('sortBy') ?? 'asc') === 'asc' ? 'desc' : 'asc';
                                        if (request('sortKey') !== $sortKey) {
                                            $sortBy = 'asc';
                                        }
                                    @endphp
                                    <th>
                                        <div class="row w-100 mx-auto">
                                            <div class="col px-0">
                                                <a @class(['btn', 'btn-outline-secondary', 'border-0'])
                                                   href="?{{ "sortKey=$sortKey&sortBy=$sortBy" }}">
                                                    {{ str($key)->upper()->toString() }}
                                                </a>
                                            </div>
                                            @if (request('sortKey') === $sortKey)
                                                <div class="col px-0">
                                                    <div @class(['row', 'w-100', 'mx-auto', 'h-100', request('sortBy') === 'desc' ? 'align-items-end' : ''])>
                                                        <div class="col px-0">
                                                            @if (request('sortBy') === 'desc')
                                                                <span class="d-inline-block text-muted" style="rotate: 90deg;">&#10140;</span>
                                                            @else
                                                                <span class="d-inline-block text-muted" style="rotate: -90deg;">&#10140;</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </th>
                                @endforeach
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                            @php($prevGroupName = null)
                            @foreach($commands as $command)
                                @php($border = ['border-bottom-0' => $loop->last])
                                @php($groupName = $command->getGroupName() ?? $command->getDirectory())
                                @if(!request('sortKey') && $groupName && $prevGroupName !== $groupName)
                                    @php($prevGroupName = $groupName)
                                    <tr>
                                        <td colspan="13" class="border-bottom-0">
                                            <h2 class="text-center h2 m-0 mt-5">{{ $prevGroupName }}</h2>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    @if(request('sortKey'))
                                        <td @class($border)>
                                            {{ $groupName }}
                                        </td>
                                    @endif
                                    <td @class($border)>
                                        <div class="row w-100 mx-auto">
                                            <div class="col px-0">
                                                <a class="btn btn-link text-decoration-none p-0 text-start" href="{{ route('chronos.edit', $command->getModel()) }}">
                                                    {{ $command->getShortName() }}
                                                </a>
                                            </div>
                                        </div>
                                        <div class="row w-100 mx-auto">
                                            <div class="col">
                                                {{ $command->getName() }}
                                            </div>
                                        </div>
                                    </td>
                                    <td @class($border)>{{ $command->getDescription() }}</td>
                                    <td @class($border)>
                                        <div class="row w-100 mx-auto">
                                            <div class="col-3 px-0 d-flex align-items-center">
                                                @if($command->runInManual())
                                                    @include('chronos::icons.on')
                                                @else
                                                    @include('chronos::icons.off')
                                                @endif
                                                <span>Manual</span>
                                            </div>
                                        </div>
                                        <div class="row w-100 mx-auto">
                                            <div class="col-3 px-0 d-flex align-items-center">
                                                @if($command->runInSchedule())
                                                    @include('chronos::icons.on')
                                                @else
                                                    @include('chronos::icons.off')
                                                @endif
                                                <span>Schedule</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td @class($border)>
                                        <table class="table m-0">
                                            <tbody>
                                            @if($command->getModel()->schedules->count())
                                                @foreach($command->getModel()->schedules as $schedule)
                                                    <tr>
                                                        <td @class(['border-bottom-0' => $loop->last, 'p-0'])>
                                                            <div class="row w-100 mx-auto">
                                                                <div class="col px-0 d-flex align-items-center">
                                                                    @if($schedule->run)
                                                                        @include('chronos::icons.on')
                                                                    @else
                                                                        @include('chronos::icons.off')
                                                                    @endif
                                                                    <span>
                                                                        {{ $times[$schedule->time_method]->getDescription($schedule) }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                            </tbody>
                                        </table>
                                    </td>
                                    <td @class($border)>
                                        @if($command->getModel()->lastRun)
                                            <div class="row w-100 mx-auto">
                                                <div class="col px-0">
                                                    @switch($command->getModel()->lastRun->state)
                                                        @case(0)
                                                            @include('chronos::icons.on')
                                                        @break
                                                        @case(1)
                                                            @include('chronos::icons.off')
                                                            @break
                                                        @case(2)
                                                            @include('chronos::icons.wait')
                                                            @break
                                                    @endswitch
                                                    <span>{{ $command->getModel()->lastRun->created_at }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    @foreach(['time', 'memory'] as $type)
                                        @foreach(['avg', 'min', 'max'] as $key)
                                            <td @class($border)>
                                                {{ $command->getModel()->metrics->{$type.'_'.$key} ?? '' }}
                                            </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
