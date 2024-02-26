@foreach($group as $command)
    @php($border = ['border-bottom-0' => $loop->last])
    <tr>
        @if(request('sortKey'))
            <td @class($border)>{{ $command['group'] }}</td>
        @endif
        <td @class($border) style="{{ request('sortKey') ?: 'padding-left: 50px;' }}">{{ $command['shortName'] }}</td>
        <td @class($border)>
            <a class="btn btn-link text-decoration-none" href="{{ route('chronos.edit', $command['model']->id) }}">
                {{ $command['signature']['name'] }}
            </a>
        </td>
        <td @class($border)>{{ $command['description'] }}</td>
            @foreach (['schedule', 'manual'] as $word)
                @php($method = 'runIn'.str($word)->studly()->toString().'Html')
                <td @class($border)>
                    @if(method_exists($command['object'], $method))
                        {!! $command['object']->$method() !!}
                    @else
                        <span class="text-success">Yes</span>
                    @endif
                </td>
            @endforeach
        <td @class($border)>
            <table class="table m-0">
                <tbody>
                @if($command['model']->schedules->count())
                    @foreach($command['model']->schedules as $schedule)
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
        <td @class($border)>
            <span class="text-{{ $command['useHandler'] ? 'success' : 'danger' }}">
                {{ $command['useHandler'] ? 'Use' : 'Not use' }}
            </span>
        </td>
        @foreach(['time', 'memory'] as $type)
            @foreach(['avg', 'min', 'max'] as $key)
                <td @class($border)>{{ $command['model']->metrics->{$type.'_'.$key} ?? '' }}</td>
            @endforeach
        @endforeach
    </tr>
@endforeach
