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
            @include('chronos::information', ['command' => $command])
            @include('chronos::schedules.edit', ['command' => $command, 'schedule' => $schedule, 'times' => $times])
            @include('chronos::statistics', ['command' => $command])
        </div>
        <div class="col-8">
            <div class="row w-100 mx-auto">
                <div class="col">
                    @include('chronos::schedules.table', ['command' => $command])
                </div>
            </div>
            <div class="row w-100 mx-auto">
                <div class="col">
                    @include('chronos::runs.table', ['runs' => $runs])
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
