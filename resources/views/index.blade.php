@extends('pulse::layout', ['title' => 'test'])
@section('content')
    <div class="row w-100 mx-auto mb-3">
        <div class="col px-0">
            <a class="btn btn-link text-decoration-none" href="{{ route('pulse.index') }}">
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
                            <th colspan="{{ request('sortKey') ? 8 : 7 }}"></th>
                            <th colspan="3" class="text-center">Time</th>
                            <th colspan="3" class="text-center">Memory</th>
                        </tr>
                        <tr>
                            @if(request('sortKey'))
                                <th>Group</th>
                            @endif
                            <th>Command</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Run in schedule</th>
                            <th>Run in manual</th>
                            <th>Info in db</th>
                            <th>Use handler</th>
                            @foreach(['time', 'memory'] as $type)
                                @foreach(['avg', 'min', 'max'] as $key)
                                    @php
                                        $sortKey = $type.'_'.$key;
                                        $sortBy = (request('sortBy') ?? 'asc') === 'asc' ? 'desc' : 'asc';
                                    @endphp
                                    <th>
                                        <a class="btn btn-link text-decoration-none" href="?{{ "sortKey=$sortKey&sortBy=$sortBy" }}">{{ str($key)->upper()->toString() }}</a>
                                    </th>
                                @endforeach
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                            @if(request('sortKey'))
                                @include('pulse::for-group', ['group' => $commands])
                            @else
                                @foreach($commands as $title => $group)
                                    <tr>
                                        <td colspan="13" @class(['border-bottom-0' => true])>
                                            <h2 class="h2 {{ $loop->index ? 'mt-5' : '' }}">{{ $title }}</h2>
                                        </td>
                                    </tr>
                                    @include('pulse::for-group', ['group' => $group])
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
