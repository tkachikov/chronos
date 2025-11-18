@php
    use Tkachikov\Chronos\Enums\TimeHelp;
@endphp

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
                            <label class="form-check-label"
                                   for="{{ $key }}">{{ $schedule?->{$key} ?? null ? 'On' : 'Off' }}</label>
                        </div>
                    </div>
                    @if($key === 'without_overlapping')
                        <div clsas="col-6">
                            <input id="{{ $key }}_time"
                                   class="form-control"
                                   type="integer"
                                   name="without_overlapping_time"
                                   value="{{ $schedule?->{$key.'_time'} ?? 1440 }}"
                                   @style(['display: none' => !$schedule?->{$key}])
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
                        <select id="time_method" class="form-control" name="time_method" form="updateForm"
                                oninput="changeMethod(this.value)">
                            @foreach($times as $method => $time)
                                <option value="{{ $method }}" @selected($schedule?->time_method === $method)>
                                    {{ $time->getTitle() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row w-100 mx-auto px-0 mt-3" id="time_params_container">
                    <div class="col px-0">
                        @foreach($times as $method => $time)
                            @if($time->params)
                                <div class="row w-100 mx-auto px-0" id="time_params_for_{{ $method }}">
                                    @foreach($time->params as $key => $param)
                                        <div class="col">
                                            <label for="time_params[{{ $method }}][{{ $key }}]">
                                                {{ $param->title }}
                                            </label>
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
                                                        class="form-control"
                                                        form="updateForm"
                                                >
                                                    @foreach($param->help->getDictionary() as $value => $name)
                                                        <option
                                                                value="{{ $value }}"
                                                                @selected(($schedule?->time_params[$key] ?? '') == $value)
                                                        >
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
                    <a data-bs-toggle="modal" data-bs-target="#deleteModal_{{ $schedule->id }}"
                       class="btn btn-danger w-100">
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