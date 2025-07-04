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
        var autoScroll = true;

        $('#sigterm').hide();
        $('#sigkill').hide();

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
                            uuidForRunInRealTime = answer.uuid;
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
            var isValidate = true;
            formData.append('_token', '{{ csrf_token() }}');
            document.querySelectorAll('[form=runCommandInRealTime]').forEach((v) => {
                if (v.type !== 'checkbox' || v.checked) {
                    if (v.hasAttribute('required') && !v.value) {
                        isValidate = false;
                        v.reportValidity();
                    }
                    formData.append(v.name, v.value);
                }
            });

            if (isValidate) {
                xhr.send(formData);
            } else {
                $('#runMessageError').hide();
                $('#runCommandInRealTime').prop('disabled', false);
            }
        }
        function getLogs() {
            var timer = setInterval(() => {
                var xhr = new XMLHttpRequest();

                let commandId = '{{ $command->getModel()->id }}';
                xhr.open('GET', `/chronos/${commandId}/run-in-real-time/${uuidForRunInRealTime}/logs`, true);

                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        let data = JSON.parse(xhr.responseText);

                        if (data.signals.sigterm) {
                            $('#sigterm').show();
                        }

                        if (data.signals.sigkill) {
                            $('#sigkill').show();
                        }

                        data.data.forEach((value, index) => {
                            if (!logs.hasOwnProperty(index)) {
                                logs[index] = value.trim();
                                setLog(value.trim());
                            }
                        });

                        if (data.status) {
                            clearInterval(timer);
                            $('#sigterm').hide();
                            $('#sigkill').hide();
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

            if (autoScroll) {
                document
                    .querySelector('#terminal .row:last-child')
                    .scrollIntoView({
                        behavior: 'smooth',
                        block: 'end',
                    });
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
        function sigterm() {
            var xhr = new XMLHttpRequest();

            let commandId = '{{ $command->getModel()->id }}';
            xhr.open('POST', `/chronos/${commandId}/run-in-real-time/${uuidForRunInRealTime}/sigterm`, true);

            var formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');

            xhr.send(formData);
        }
        function sigkill() {
            var xhr = new XMLHttpRequest();

            let commandId = '{{ $command->getModel()->id }}';
            xhr.open('POST', `/chronos/${commandId}/run-in-real-time/${uuidForRunInRealTime}/sigkill`, true);

            var formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');

            xhr.send(formData);
        }

        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

        const terminal = document.getElementById('terminal');

        terminal.addEventListener('scroll', () => {
            autoScroll = terminal.scrollTop + terminal.clientHeight >= terminal.scrollHeight - 10;
        });
    </script>
@endsection
