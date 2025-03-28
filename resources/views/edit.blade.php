@php
    use Illuminate\Support\Js;
@endphp
@extends('chronos::layout')
@section('content')
    <form id="updateForm" method="POST" action="{{ route('chronos.update', $command->getModel()) }}">
        @csrf
        <input type="hidden" name="command_id" value="{{ $command->getModel()->id }}">
        @if($schedule?->id)
            <input type="hidden" name="id" value="{{ $schedule->id }}">
        @endif
    </form>
    @if($schedule?->id)
        <form id="deleteForm" method="POST" action="{{ route('chronos.schedules.destroy', ['command' => $command->getModel(), 'schedule' => $schedule->id]) }}">
            @csrf
            @method('DELETE')
        </form>
    @endif
    @if($command->runInManual())
        <form id="runCommand" method="POST" action="{{ route('chronos.run', $command->getModel()) }}">
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
                        @include('chronos::args', ['command' => $command, 'form' => 'runCommand'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal" class="btn btn-secondary">Close</button>
                        <button type="submit" class="btn btn-success" form="runCommand">Run</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="runCommandInRealTimeModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        Args for run command
                        <button type="button" data-bs-dismiss="modal" class="btn-close" aria-label="Close"></button>
                    </div>
                    <div class="text-center m-3 modal-body">
                        <div class="row mx-auto w-100 mb-5">
                            <div class="col">
                                @include('chronos::args', ['command' => $command, 'form' => 'runCommandInRealTime'])
                            </div>
                            <div class="col"></div>
                        </div>
                        <div class="row mx-auto w-100">
                            <div class="col">
                                <div id="terminal" class="mx-auto text-start" style="width: 800px; height: 600px; background: black; color: white; overflow: auto;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <span id="runMessageError" class="text-danger"></span>
                        <button type="button" data-bs-dismiss="modal" class="btn btn-secondary">Close</button>
                        <button id="runCommandInRealTime" class="btn btn-danger" onclick="runRealTime()">Run</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="row w-100 mx-auto mb-3">
        <div class="col d-flex align-items-center">
            <a class="btn btn-link text-decoration-none" href="{{ route('chronos.main') }}">
                <h1 class="h1 m-0">
                    Commands
                </h1>
            </a>
            <h1 class="h1 m-0">
                / {{ $command->getShortName() }}
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
                        </div>
                    </h2>
                </div>
                <div class="card-body">
                    @if($command->runInManual())
                        <div class="row w-100 mx-auto py-3 align-items-center">
                            <div class="col text-center">
                                <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#runCommandInRealTimeModal">
                                    Run in real time
                                </button>
                                @if(
                                    empty($command->getDefinition()->getArguments())
                                    && empty($command->getDefinition()->getOptions())
                                )
                                    <button type="submit" class="btn btn-success" form="runCommand">
                                        Run from queue
                                    </button>
                                @else
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#runCommandModal">
                                        Run from queue
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                    <div class="row w-100 mx-auto py-3 align-items-center">
                        <div class="col-4">Short name</div>
                        <div class="col-8">{{ $command->getShortName() }}</div>
                    </div>
                    <div class="row w-100 mx-auto py-3 align-items-center">
                        <div class="col-4">Full name</div>
                        <div class="col-8">{{ $command->getFullName() }}</div>
                    </div>
                    <div class="row w-100 mx-auto py-3 align-items-center">
                        <div class="col-4">Description</div>
                        <div class="col-8">{{ $command->getDescription() }}</div>
                    </div>
                    <div class="row w-100 mx-auto py-3 align-items-center">
                        <div class="col-4">Class</div>
                        <div class="col-8">{{ $command->getClassName() }}</div>
                    </div>
                    <div class="row w-100 mx-auto py-3 align-items-center">
                        <div class="col-4">Signature</div>
                        <div class="col-8">{{ $command->getSignature() }}</div>
                    </div>
                    <div class="row w-100 mx-auto py-3 align-items-center">
                        <div class="col-4">Run in schedule</div>
                        <div class="col-8">
                            @if($command->runInSchedule())
                                <span class="text-success">Yes</span>
                            @else
                                <span class="text-danger">No</span>
                            @endif
                        </div>
                    </div>
                    <div class="row w-100 mx-auto py-3 align-items-center">
                        <div class="col-4">Run in manual</div>
                        <div class="col-8">
                            @if($command->runInManual())
                                <span class="text-success">Yes</span>
                            @else
                                <span class="text-danger">No</span>
                            @endif
                        </div>
                    </div>
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
                            <div class="col-8 d-flex">
                                <div class="col">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               role="switch"
                                               id="{{ $key }}"
                                               name="{{ $key }}"
                                               form="updateForm"
                                               onchange="changeSystem(this)">
                                        <label class="form-check-label" for="{{ $key }}">{{ $schedule?->{$key} ?? null ? 'On' : 'Off' }}</label>
                                    </div>
                                </div>
                                @if($key === 'without_overlapping')
                                    <div clsas="col-6">
                                        <input id="{{ $key }}_time"
                                               class="form-control"
                                               type="integer"
                                               name="without_overlapping_time"
                                               value="{{ $schedule?->{$key.'_time'} ?? 1440 }}" @style(['display: none' => !$schedule?->{$key}])
                                               form="updateForm">
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    <div class="row w-100 mx-auto py-3 border-bottom align-items-center">
                        <div class="col-4">
                            <label for="time_method">
                                Time
                            </label>
                        </div>
                        <div class="col-8 px-0">
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
                            </div>
                            <div class="row w-100 mx-auto px-0 mt-3" id="time_params_container">
                                <div class="col px-0">
                                    @foreach($times as $method => $options)
                                        @if($options['params'])
                                            <div class="row w-100 mx-auto px-0" id="time_params_for_{{ $method }}">
                                                @foreach($options['params'] as $key => $param)
                                                    <div class="col">
                                                        <label for="time_params[{{ $key }}]">
                                                            {{ $param['name'] }}
                                                        </label>
                                                        <input
                                                            type="text"
                                                            id="time_params[{{ $key }}]"
                                                            name="time_params[{{ $key }}]"
                                                            class="form-control"
                                                            form="updateForm"
                                                            value="{{ $schedule?->time_params[$key] ?? $param['default'] ?? '' }}"
                                                        >
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @if(filled($command->getDefinition()->getOptions()) || $command->getDefinition()->getArgumentCount())
                        <div class="row w-100 mx-auto py-3 border-bottom align-items-center">
                            <div class="col-4">
                                <label for="args">Args</label>
                            </div>
                            <div class="col-8">
                                @include('chronos::args', ['command' => $command, 'form' => 'updateForm'])
                            </div>
                        </div>
                    @endif
                    <div class="row w-100 mx-auto py-3 align-items-center justify-content-center">
                        @if($schedule?->id)
                            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                                <a data-bs-toggle="modal" data-bs-target="#deleteModal_{{ $schedule->id }}" class="btn btn-danger w-100">
                                    Delete
                                </a>
                                @include('chronos::delete-modal', ['id' => 'deleteModal_has_'.$schedule->id, 'action' => route('chronos.schedules.destroy', ['command' => $command->getModel(), 'schedule' => $schedule->id])])
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                                <a href="{{ route('chronos.edit', $command->getModel()) }}" class="btn btn-secondary w-100">New</a>
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
                    @if($command->getModel()->metrics()->count())
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
                                        <td>{{ $command->getModel()->metrics->{$type.'_'.$key} ?? '' }}</td>
                                    @endforeach
                                @endforeach
                            </tr>
                            </tbody>
                        </table>
                    @else
                        Not found statistics
                    @endif
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
                                @if(!$command->getModel()->schedules->count())
                                    <tr>
                                        <td colspan="8" @class(['border-bottom-0' => true])>No schedules</td>
                                    </tr>
                                @endif
                                @foreach($command->getModel()->schedules as $item)
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
                                            @if($item->user_id && ($user = $item->userWithTrashed()->first()))
                                                {{ $user->email }}
                                            @endif
                                        </td>
                                        @foreach(['run', 'without_overlapping', 'run_in_background'] as $key)
                                            <td @class($border)>
                                                    <span class="text-{{ $item->{$key} ? 'success' : 'danger' }}">
                                                        {{ $item->{$key} ? 'On' : 'Off' }}
                                                    </span>
                                            </td>
                                        @endforeach
                                        <td @class($border)>
                                            {{ $times[$item->time_method]['title'] }}
                                            @if($item->time_params)
                                                @if(count($item->time_params) === 1)
                                                    {{ $item->time_params[0] }}
                                                @else
                                                    @foreach($times[$item->time_method]['params'] as $key => $param)
                                                        <div class="row w-100 mx-auto">
                                                            <div class="px-0">
                                                                {{ $param['name'] }}: {{ $item->time_params[$key] }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            @endif
                                        </td>
                                        <td @class($border)>
<pre class="m-0">$schedule
    ->command({{ $command->getShortName() . '::class' . ($args ? ", {$args}" : '') }})
    ->{{ $item->time_method }}({{ $item->time_params ? collect($item->time_params)->map(fn ($v) => "'$v'")->implode(', ') : '' }}){{
    $item->without_overlapping ? "\r\n    ->withoutOverlapping(" . ($item->without_overlapping_time !== 1440 ? $item->without_overlapping_time : '') . ')' : ''
}}{{ $item->run_in_background ? "\r\n    ->runInBackground()" : '' }};</pre>
                                        </td>
                                        <td @class($border)>
                                            <div class="row w-100 mx-auto">
                                                <div class="col">
                                                    <a href="{{ route('chronos.edit', ['command' => $command->getModel(), 'schedule' => $item->id]) }}">
                                                        @include('chronos::icons.edit')
                                                    </a>
                                                </div>
                                                <div class="col">
                                                    <a data-bs-toggle="modal" data-bs-target="#deleteModal_{{ $item->id }}" class="text-danger">
                                                        @include('chronos::icons.bucket')
                                                    </a>
                                                    @include('chronos::delete-modal', ['id' => 'deleteModal_'.$item->id, 'action' => route('chronos.schedules.destroy', ['command' => $command->getModel(), 'schedule' => $item->id])])
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
                                @if(!$runs->count())
                                    <tr>
                                        <td colspan="5" @class(['border-bottom-0' => true])>No runs</td>
                                    </tr>
                                @endif
                                @foreach($runs as $run)
                                    @php($border = ['border-bottom-0' => $loop->last])
                                    <tr>
                                        <td @class($border)>{{ $run->created_at }}</td>
                                        <td @class($border)>{{ $run->created_at->diffInSeconds($run->updated_at) }}</td>
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
                                    {{ $runs->appends(request()->query())->links('chronos::pagination') }}
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

        function resetMethodParams() {
            $('#time_params_container').hide();
            $('#time_params_container [id*="time_params_for_"]').hide();
            $('#time_params_container input').prop('disabled', true);
        }

        function changeMethod(method) {
            let hasParams = methods[method]['params'].length !== 0;

            resetMethodParams();

            if (!hasParams) {
                return;
            }

            $('#time_params_container').show();
            $(`#time_params_container [id="time_params_for_${method}"]`).show();
            $(`#time_params_container [id="time_params_for_${method}"] input`).prop('disabled', false);
        }

        function changeSystem(el) {
            $(el).next().text(el.checked ? 'On' : 'Off');
            if ($(el).attr('id') === 'without_overlapping') {
                $('#without_overlapping_time').toggle();
            }
        }

        resetMethodParams();

        @if($schedule?->time_method)
        changeMethod('{{ $schedule->time_method }}');
        @else
        let firstMethod = Object.getOwnPropertyNames(methods)[0];
        changeMethod(firstMethod);
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

        var uuidForRunInRealTime;
        var logs = [];

        function runRealTime() {
            $('#runMessageError').hide();
            $('#runCommandInRealTime').prop('disabled', true);

            var xhr = new XMLHttpRequest();

            let commandId = '{{ $command->getModel()->id }}';
            xhr.open('POST', `/chronos/${commandId}/run-in-real-time`, true);

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        let answer = JSON.parse(xhr.responseText);

                        if (answer.uuid !== null) {
                            uuidForRunInRealTime = JSON.parse(xhr.responseText).uuid;
                            logs = [];
                            document.getElementById('terminal').innerHTML = '';
                            getLogs();
                        } else if (answer.message !== null) {
                            $('#runMessageError').show();
                            $('#runMessageError').text(answer.message);
                        }
                    }
                }
            };

            var formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            document.querySelectorAll('[form=runCommandInRealTime]').forEach((v) => {
                if (v.type !== 'checkbox' || v.checked) {
                    formData.append(v.name, v.value);
                }
            });

            xhr.send(formData);
        }
        function getLogs() {
            var timer = setInterval(() => {
                var xhr = new XMLHttpRequest();

                let commandId = '{{ $command->getModel()->id }}';
                xhr.open('GET', `/chronos/${commandId}/run-in-real-time/${uuidForRunInRealTime}/logs`, true);

                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        let data = JSON.parse(xhr.responseText);
                        data.data.forEach((value, index) => {
                            if (!logs.hasOwnProperty(index)) {
                                logs[index] = value.trim();
                                setLog(value.trim());
                            }
                        });
                        if (data.status) {
                            clearInterval(timer);
                            $('#runMessageError').hide();
                            $('#runCommandInRealTime').prop('disabled', false);
                        }
                    }
                };

                xhr.send();
            }, 1000);
        }
        function setLog(message) {
            if (message === ':wait:') {
                message = `<span contenteditable="true" tabindex="0" style="outline: none; margin-left: 10px;" class="w-100 entering" onkeyup="sendAnswer(event)"></span>`;
                document.querySelector('#terminal > div:last-child > div > pre').innerHTML += message;
                document.querySelector('#terminal > div:last-child .entering').focus();
            } else {
                message = `<div class="row mx-auto w-100 py-1"><div class="col pl-5"><pre class="m-0">${message}</pre></div></div>`;
                document.getElementById('terminal').innerHTML += message;
            }
        }
        function sendAnswer(event) {
            if (event.code === 'Enter') {
                event.target.blur();
                event.target.setAttribute('contenteditable', false);
                var xhr = new XMLHttpRequest();

                let commandId = '{{ $command->getModel()->id }}';
                xhr.open('POST', `/chronos/${commandId}/run-in-real-time/${uuidForRunInRealTime}/answer`, true);

                var formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('answer', event.target.textContent);

                xhr.send(formData);
            }
        }
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
    </script>
@endsection
