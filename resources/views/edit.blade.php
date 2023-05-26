@php
    use Illuminate\Support\Js;
@endphp
@extends('commands::layout')
@section('content')
    <form id="updateForm" method="POST" action="{{ route('commands.update', $command['model']->id) }}">
        @csrf
        <input type="hidden" name="command_id" value="{{ $command['model']->id }}">
        @if($schedule?->id)
            <input type="hidden" name="id" value="{{ $schedule->id }}">
        @endif
    </form>
    @if($schedule?->id)
        <form id="deleteForm" method="POST" action="{{ route('commands.schedules.destroy', ['command' => $command['model']->id, 'schedule' => $schedule->id]) }}">
            @csrf
            @method('DELETE')
        </form>
    @endif
    @if(!method_exists($command['object'], 'runInManual') || $command['object']->runInManual())
        <form id="runCommand" method="POST" action="{{ route('commands.run', $command['model']->id) }}">
            @csrf
        </form>
        <div class="modal fade" id="runCommandModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        Args for run command
                        <button type="button" data-bs-dismiss="modal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="text-center m-3 modal-body">
                        @include('commands::args', ['command' => $command, 'form' => 'runCommand'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal" class="btn btn-secondary">Close</button>
                        <button type="submit" class="btn btn-success" form="runCommand">Run</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="row w-100 mx-auto mb-3">
        <div class="col d-flex align-items-center">
            <a class="btn btn-link text-decoration-none" href="{{ route('commands.index') }}">
                <h1 class="h1 m-0">
                    Commands
                </h1>
            </a>
            <h1 class="h1 m-0">
                / {{ $command['name'] }}
            </h1>
        </div>
    </div>
    <div class="row w-100 mx-auto">
        <div class="col-4">
            <div class="card shadow mb-5">
                <div class="card-header">
                    <h2 class="h2 m-0 text-muted">
                        <div class="row w-100 mx-auto">
                            <div class="col">Main information</div>
                            <div class="col text-end">
                                @if(!method_exists($command['object'], 'runInManual') || $command['object']->runInManual())
                                    @if($command['useHandler'] && ($command['signature']['arguments'] || $command['signature']['options']))
                                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#runCommandModal">
                                            @include('commands::icons.play')
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-success" form="runCommand">
                                            @include('commands::icons.play')
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </h2>
                </div>
                <div class="card-body">
                    @foreach($data as $label => $value)
                        <div class="row w-100 mx-auto py-3 align-items-center {{ $loop->remaining ? 'border-bottom' : '' }}">
                            <div class="col-4">{{ $label }}</div>
                            <div class="col-8">{!! $value !!}</div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card shadow mb-5">
                <div class="card-header">
                    <h2 class="h2 m-0 text-muted">
                        @if($schedule?->id)
                            Edit
                        @else
                            Create
                        @endif
                        schedule
                    </h2>
                </div>
                <div class="card-body">
                    @if($schedule?->id)
                        <div class="row w-100 mx-auto py-3 border-bottom align-items-center">
                            <div class="col-4">
                                <label>
                                    ID
                                </label>
                            </div>
                            <div class="col-8">
                                {{ $schedule->id }}
                            </div>
                        </div>
                    @endif
                    @foreach(['run', 'without_overlapping', 'run_in_background'] as $key)
                        <div class="row w-100 mx-auto py-3 border-bottom align-items-center">
                            <div class="col-4">
                                <label for="run">{{ str($key)->replace('_', ' ')->ucfirst() }}</label>
                            </div>
                            <div class="col-8">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           role="switch"
                                           id="{{ $key }}"
                                           name="{{ $key }}"
                                           form="updateForm"
                                           onchange="$(this).next().text(this.checked ? 'On' : 'Off')">
                                    <label class="form-check-label" for="{{ $key }}">{{ $schedule?->{$key} ?? null ? 'On' : 'Off' }}</label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="row w-100 mx-auto py-3 border-bottom align-items-center">
                        <div class="col-4">
                            <label for="time_method">
                                Time
                            </label>
                        </div>
                        <div class="col-8">
                            <div class="row w-100 mx-auto">
                                <div class="col">
                                    <select id="time_method" class="form-control" name="time_method" form="updateForm" oninput="changeMethod(this.value)">
                                        @foreach($times as $method => $params)
                                            <option value="{{ $method }}" @selected($schedule?->time_method === $method)>
                                                {{ $params['title'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col">
                                    <input id="time_params" type="text" name="time_params" class="form-control" form="updateForm" value="{{ $schedule?->time_params }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($command['signature']['arguments'] || $command['signature']['options'])
                        <div class="row w-100 mx-auto py-3 border-bottom align-items-center">
                            <div class="col-4">
                                <label for="args">Args</label>
                            </div>
                            <div class="col-8">
                                @include('commands::args', ['command' => $command, 'form' => 'updateForm'])
                            </div>
                        </div>
                    @endif
                    <div class="row w-100 mx-auto py-3 align-items-center justify-content-center">
                        @if($schedule?->id)
                            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                                <a data-bs-toggle="modal" data-bs-target="#deleteModal_{{ $schedule->id }}" class="btn btn-danger w-100">
                                    Delete
                                </a>
                                @include('commands::delete-modal', ['id' => 'deleteModal_has_'.$schedule->id, 'action' => route('commands.schedules.destroy', ['command' => $command['model']->id, 'schedule' => $schedule->id])])
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                                <a href="{{ route('commands.edit', $command['model']->id) }}" class="btn btn-secondary w-100">New</a>
                            </div>
                        @endif
                        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                            <button type="submit" class="btn btn-primary w-100" form="updateForm">Save</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow">
                <div class="card-header">
                    <h2 class="h2 m-0 text-muted">
                        Statistics
                    </h2>
                </div>
                <div class="card-body">
                    <table class="table m-0 text-center table-bordered">
                        <thead>
                        <tr>
                            <th colspan="3">Time</th>
                            <th colspan="3">Memory</th>
                        </tr>
                        <tr>
                            <th>AVG</th>
                            <th>MIN</th>
                            <th>MAX</th>
                            <th>AVG</th>
                            <th>MIN</th>
                            <th>MAX</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            @foreach(['time', 'memory'] as $type)
                                @foreach(['avg', 'min', 'max'] as $key)
                                    <td>{{ $command['statistics'][$type.'_'.$key] ?? '' }}</td>
                                @endforeach
                            @endforeach
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-8">
            <div class="row w-100 mx-auto">
                <div class="col">
                    <div class="card shadow mb-5">
                        <div class="card-header">
                            <h2 class="h2 m-0 text-muted">Schedules</h2>
                        </div>
                        <div class="card-body">
                            <table class="table m-0">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Run</th>
                                    <th>With overlapping</th>
                                    <th>Run in background</th>
                                    <th>Time</th>
                                    <th>In code</th>
                                    <th>Edit</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if(!$command['model']->schedules->count())
                                    <tr>
                                        <td colspan="8" @class(['border-bottom-0' => true])>No schedules</td>
                                    </tr>
                                @endif
                                @foreach($command['model']->schedules as $item)
                                    @php
                                        $border = ['border-bottom-0' => true];
                                        $args = !$item->args ? '' : str(json_encode($item->preparedArgs))
                                            ->replace('{', '[')
                                            ->replace('}', ']')
                                            ->replace(':', ' => ')
                                            ->replace('"', "'")
                                            ->toString();
                                    @endphp
                                    <tr @class(['bg-light' => $item->id == $schedule?->id])>
                                        <td @class($border)>{{ $item->id }}</td>
                                        <td @class($border)>
                                            @if($item->user_id && ($user = $item->user()->withTrashed()->first()))
                                                <a href="{{ route('admin.user.edit', $item->user_id) }}" target="_blank">
                                                    {{ $user->email }}
                                                </a>
                                            @endif
                                        </td>
                                        @foreach(['run', 'without_overlapping', 'run_in_background'] as $key)
                                            <td @class($border)>
                                                    <span class="text-{{ $item->{$key} ? 'success' : 'danger' }}">
                                                        {{ $item->{$key} ? 'On' : 'Off' }}
                                                    </span>
                                            </td>
                                        @endforeach
                                        <td @class($border)>{{ $times[$item->time_method]['title'] . ($item->time_params ? " {$item->time_params}" : '') }}</td>
                                        <td @class($border)>
<pre class="m-0">$schedule
    ->command({{ $command['name'] . '::class' . ($args ? ", {$args}" : '') }})
    ->{{ $item->time_method }}({{ $item->time_params ? "'{$item->time_params}'" : '' }}){{
    $item->without_overlapping ? "\r\n    ->withoutOverlapping(2)" : ''
}}{{ $item->run_in_background ? "\r\n    ->runInBackground()" : '' }}</pre>
                                        </td>
                                        <td @class($border)>
                                            <div class="row w-100 mx-auto">
                                                <div class="col">
                                                    <a href="{{ route('commands.edit', ['command' => $command['model']->id, 'schedule' => $item->id]) }}">
                                                        @include('commands::icons.edit')
                                                    </a>
                                                </div>
                                                <div class="col">
                                                    <a data-bs-toggle="modal" data-bs-target="#deleteModal_{{ $item->id }}" class="text-danger">
                                                        @include('commands::icons.bucket')
                                                    </a>
                                                    @include('commands::delete-modal', ['id' => 'deleteModal_'.$item->id, 'action' => route('commands.schedules.destroy', ['command' => $command['model']->id, 'schedule' => $item->id])])
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row w-100 mx-auto">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header">
                            <div class="row w-100 mx-auto">
                                <div class="col">
                                    <h2 class="h2 m-0 text-muted">Runs</h2>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table m-0">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Exec (sec)</th>
                                    <th>Memory</th>
                                    <th>Schedule ID</th>
                                    <th>Telescope</th>
                                    <th>State</th>
                                    <th class="w-50">Logs</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if(!$command['useHandler'])
                                    <tr>
                                        <td colspan="5" @class(['border-bottom-0' => true])>
                                                <span class="text-danger">
                                                    For record runs and logs set extends CommandHandler in this Command
                                                </span>
                                        </td>
                                    </tr>
                                @elseif(!$runs->count())
                                    <tr>
                                        <td colspan="5" @class(['border-bottom-0' => true])>No runs</td>
                                    </tr>
                                @endif
                                @foreach($runs as $run)
                                    @php($border = ['border-bottom-0' => $loop->last])
                                    <tr>
                                        <td @class($border)>{{ $run->created_at }}</td>
                                        <td @class($border)>{{ $run->updated_at->diffInSeconds($run->created_at) }}</td>
                                        <td @class($border)>{{ $run->memory }}</td>
                                        <td @class($border)>{{ $run->schedule_id }}</td>
                                        <td @class($border)>
                                            @if($run->telescope_id)
                                                @if(config('telescope.enabled'))
                                                    <a href="{{ route('telescope', "commands/{$run->telescope_id}") }}" target="_blank">link</a>
                                                @else
                                                    <span class="text-danger">Off</span>
                                                @endif
                                            @else
                                                no link
                                            @endif
                                        </td>
                                        <td @class($border)>
                                                <span class="text-{{ $run->stateCss }}">
                                                    {{ $run->stateTitle }}
                                                </span>
                                        </td>
                                        <td @class($border)>
                                            <table class="table m-0">
                                                <tbody>
                                                @foreach($logs[$run->id] as $log)
                                                    @php($borderLog = ['border-bottom-0' => $loop->last])
                                                    <tr>
                                                        <td @class(array_merge($borderLog, ['w-75' => true]))>
                                                                    <span class="border-3 border-start border-{{ $log->type->css() }} px-3">
                                                                        {{ $log->message }}
                                                                    </span>
                                                        </td>
                                                        <td @class($borderLog)>{{ $log->created_at }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                            <div class="row w-100 mx-auto">
                                                <div class="col">
                                                    {{ $logs[$run->id]->appends(request()->query())->links() }}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            <div class="row w-100 mx-auto">
                                <div class="col">
                                    {{ $runs->appends(request()->query())->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('footer_scripts')
    <script>
        var methods = {{ Js::from($times) }};
        $('#time_params').parent().hide();
        function changeMethod(method) {
            $('#time_params').parent()[methods[method]['params'] ? 'show' : 'hide']();
            @if($schedule?->time_params)
            if (method == {{ Js::from($schedule->time_method) }}) {
                $('#time_params').val({{ Js::from($schedule->time_params) }});
            } else {
                $('#time_params').val('');
            }
            @endif
        }
        @if($schedule?->time_method)
        changeMethod('{{ $schedule->time_method }}');
        @endif
        @foreach(['run', 'without_overlapping', 'run_in_background'] as $key)
        @if($schedule?->{$key})
        $('#{{ $key }}').prop('checked', true)
        @endif
        @endforeach
        @if($schedule?->args)
        @foreach($schedule->args as $key => $value)
        @if(is_bool($value) && $value)
        $('[name="args[{{ $key }}]"]').prop('checked', true);
        @endif
        @endforeach
        @endif
    </script>
@endsection
