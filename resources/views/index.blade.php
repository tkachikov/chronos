@extends('chronos::layout', ['title' => 'test'])
@section('content')
    <div class="row w-100 mx-auto mb-3">
        <div class="col px-0">
            <a class="btn btn-link text-decoration-none" href="{{ route('chronos.main') }}">
                <h1 class="h1 m-0">Commands</h1>
            </a>
        </div>
    </div>
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
                            <th>Command</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Run in schedule</th>
                            <th>Run in manual</th>
                            <th>Info in db</th>
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
                            @php($prevDirectory = null)
                            @foreach($commands as $command)
                                @php($border = ['border-bottom-0' => $loop->last])
                                @php($directory = $command->getDirectory())
                                @if(!request('sortKey') && $directory && $prevDirectory !== $directory)
                                    @php($prevDirectory = $directory)
                                    <tr>
                                        <td colspan="13" class="border-bottom-0">
                                            <h2 class="text-center h2 m-0 mt-5">{{ $prevDirectory }}</h2>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td @class($border) style="{{ request('sortKey') ?: 'padding-left: 50px;' }}">{{ $command->getShortName() }}</td>
                                    <td @class($border)>
                                        <a class="btn btn-link text-decoration-none" href="{{ route('chronos.edit', $command->getModel()) }}">
                                            {{ $command->getName() }}
                                        </a>
                                    </td>
                                    <td @class($border)>{{ $command->getDescription() }}</td>
                                    @foreach (['runInSchedule', 'runInManual'] as $runMethod)
                                        <td @class($border)>
                                            @if($command->$runMethod())
                                                <span class="text-success">Yes</span>
                                            @else
                                                <span class="text-danger">No</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td @class($border)>
                                        <table class="table m-0">
                                            <tbody>
                                            @if($command->getModel()->schedules->count())
                                                @foreach($command->getModel()->schedules as $schedule)
                                                    <tr>
                                                        <td @class(['border-bottom-0' => $loop->last])>{{ $times[$schedule->time_method]['title'] . ($schedule->time_params ? " {$schedule->time_params}" : '') }}</td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td class="border-bottom-0">
                                                        <span class="text-danger">{{ "hasn't in db" }}</span>
                                                    </td>
                                                </tr>
                                            @endif
                                            </tbody>
                                        </table>
                                    </td>
                                    @foreach(['time', 'memory'] as $type)
                                        @foreach(['avg', 'min', 'max'] as $key)
                                            <td @class($border)>{{ $command->getModel()->metrics->{$type.'_'.$key} ?? '' }}</td>
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
