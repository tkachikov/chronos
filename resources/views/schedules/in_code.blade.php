@php
    // A value as in PHP: a number, null, a string in quotes, an array of them
    $export = function ($value) use (&$export): string {
        return match (true) {
            is_array($value) => '[' . implode(', ', array_map($export, $value)) . ']',
            is_null($value) => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_numeric($value) => (string) $value,
            default => "'" . addcslashes((string) $value, "'\\") . "'",
        };
    };
@endphp
<pre class="m-0">$schedule
    ->command(@if($item->preparedArgs)&nbsp;
        {{ $command->getShortName() . '::class'}},
        [
@foreach($item->preparedArgs as $key => $arg)
            @if(is_string($key)){{ $export($key) }} => @endif{{ $export($arg) }},
@endforeach
        ],
    )
    @else{{ $command->getShortName() . '::class' }})
    @endif->{{ $item->time_method }}({{ $item->time_params ? collect($item->time_params)->map(fn ($v) => "'$v'")->implode(', ') : '' }}){{
    $item->without_overlapping ? "\r\n    ->withoutOverlapping(" . ($item->without_overlapping_time !== 1440 ? $item->without_overlapping_time : '') . ')' : ''
}}{{ $item->run_in_background ? "\r\n    ->runInBackground()" : '' }};</pre>
