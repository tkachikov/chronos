<pre class="m-0">$schedule
    ->command(@if($item->preparedArgs)&nbsp;
        {{ $command->getShortName() . '::class'}},
        [
@foreach($item->preparedArgs as $key => $arg)
            '{{ $key }}' => {{ is_numeric($arg) ? $arg : (is_null($arg) ? 'null' : "'$arg'") }},
@endforeach
        ],
    )
    @else{{ $command->getShortName() . '::class' }})
    @endif->{{ $item->time_method }}({{ $item->time_params ? collect($item->time_params)->map(fn ($v) => "'$v'")->implode(', ') : '' }}){{
    $item->without_overlapping ? "\r\n    ->withoutOverlapping(" . ($item->without_overlapping_time !== 1440 ? $item->without_overlapping_time : '') . ')' : ''
}}{{ $item->run_in_background ? "\r\n    ->runInBackground()" : '' }};</pre>