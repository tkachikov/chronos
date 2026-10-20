{{-- The form of a schedule in a window: a new one, or the one of ?schedule= --}}
@php
    use Tkachikov\Chronos\Enums\TimeHelp;

    $switches = [
        'run' => ['Run', 'Off keeps the schedule, but it is not run'],
        'without_overlapping' => ['Without overlapping', 'A run is skipped while the previous one is going on'],
        'run_in_background' => ['In background', 'The scheduler does not wait for the command and starts the next tasks at once'],
    ];
    $editing = (bool) $schedule?->id;
@endphp
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true" @if($formOpen) data-open @endif>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <span id="scheduleModalLabel">{{ $editing ? 'Edit schedule' : 'New schedule' }}</span>
                @if($editing)
                    <span class="cx-count">#{{ $schedule->id }}</span>
                    @if($schedule->name)
                        <span class="cx-modal-sub">{{ $schedule->name }}</span>
                    @endif
                @endif
                <button type="button" data-bs-dismiss="modal" class="btn-close" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if(isset($errors) && $errors->any())
                    <div class="cx-errors" role="alert">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="cx-fields">
                    <div class="cx-field">
                        <label for="name">Name</label>
                        <input id="name"
                               class="form-control"
                               type="text"
                               name="name"
                               maxlength="255"
                               placeholder="e.g. Nightly import"
                               value="{{ old('name', $schedule?->name) }}"
                               form="updateForm">
                        <small class="cx-field-hint">Optional, it tells the schedules of one command apart.</small>
                    </div>

                    <div class="cx-field">
                        <label for="time_method">When</label>
                        <select id="time_method" class="form-select" name="time_method" form="updateForm" oninput="changeMethod(this.value)">
                            @foreach($times as $method => $time)
                                <option value="{{ $method }}" @selected(old('time_method', $schedule?->time_method) === $method)>
                                    {{ $time->getTitle() }}
                                </option>
                            @endforeach
                        </select>
                        <div class="cx-params" id="time_params_container">
                            @foreach($times as $method => $time)
                                @if($time->params)
                                    <div id="time_params_for_{{ $method }}">
                                        @foreach($time->params as $key => $param)
                                            <div>
                                                <label for="time_params[{{ $method }}][{{ $key }}]">{{ $param->title }}</label>
                                                @if($param->help === TimeHelp::Time)
                                                    <input
                                                            type="time"
                                                            id="time_params[{{ $method }}][{{ $key }}]"
                                                            name="time_params[{{ $method }}][{{ $key }}]"
                                                            class="form-control"
                                                            form="updateForm"
                                                            value="{{ $schedule?->time_params[$key] ?? $param->default ?? '' }}"
                                                    >
                                                @elseif($param->help instanceof TimeHelp)
                                                    <select
                                                            id="time_params[{{ $method }}][{{ $key }}]"
                                                            name="time_params[{{ $method }}][{{ $key }}]"
                                                            class="form-select"
                                                            form="updateForm"
                                                    >
                                                        @foreach($param->help->getDictionary() as $value => $name)
                                                            <option value="{{ $value }}" @selected(($schedule?->time_params[$key] ?? '') == $value)>
                                                                {{ $name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input
                                                            type="text"
                                                            id="time_params[{{ $method }}][{{ $key }}]"
                                                            name="time_params[{{ $method }}][{{ $key }}]"
                                                            class="form-control"
                                                            form="updateForm"
                                                            value="{{ $schedule?->time_params[$key] ?? $param->default ?? '' }}"
                                                    >
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="cx-field wide">
                        <div class="cx-switches">
                            @foreach($switches as $key => [$title, $hint])
                                @php
                                    $on = (bool) old($key, $schedule?->{$key} ?? ($key === 'run' && !$editing));
                                @endphp
                                <div @class(['cx-switch-box', 'on' => $on])>
                                    <div class="cx-switch-row form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               role="switch"
                                               id="{{ $key }}"
                                               name="{{ $key }}"
                                               form="updateForm"
                                               onchange="changeSystem(this)"
                                               @checked($on)>
                                        <label for="{{ $key }}"><b>{{ $title }}</b><span data-state>{{ $on ? 'On' : 'Off' }}</span></label>
                                    </div>
                                    <small>{{ $hint }}</small>
                                    @if($key === 'without_overlapping')
                                        <div class="cx-minutes" id="without_overlapping_time_wrap" @style(['display: none' => !$on])>
                                            <input id="without_overlapping_time"
                                                   class="form-control"
                                                   type="number"
                                                   min="1"
                                                   name="without_overlapping_time"
                                                   value="{{ old('without_overlapping_time', $schedule?->without_overlapping_time ?? 1440) }}"
                                                   form="updateForm">
                                            <span>minutes until the lock expires</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if(filled($command->getDefinition()->getOptions()) || $command->getDefinition()->getArgumentCount())
                        <div class="cx-field wide">
                            <span class="cx-label">Arguments</span>
                            @include('chronos::args', ['command' => $command, 'form' => 'updateForm'])
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                @if($editing)
                    <button type="button" class="cx-btn danger me-auto" data-bs-toggle="modal" data-bs-target="#deleteModal_form">Delete</button>
                @endif
                <button type="button" data-bs-dismiss="modal" class="cx-btn ghost">Cancel</button>
                <button type="submit" class="cx-btn primary" form="updateForm">{{ $editing ? 'Save' : 'Add schedule' }}</button>
            </div>
        </div>
    </div>
</div>
@if($editing)
    @include('chronos::delete-modal', ['id' => 'deleteModal_form', 'action' => route('chronos.schedules.destroy', ['command' => $command->getModel(), 'schedule' => $schedule->id])])
@endif
