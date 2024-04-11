@foreach(['arguments' => '', 'options' => '--'] as $key => $prefix)
    @foreach($command->getDefinition()->{'get'.ucfirst($key)}() as $input)
        @php
            $name = $prefix.$input->getName();
            $description = $input->getDescription();
        @endphp
        <div class="row w-100 mx-auto align-items-center">
            <div class="col">
                @if($description)
                    <span data-bs-toggle="tooltip" data-bs-title="{{ $description }}" data-bs-placement="top">
                        {{ $name }}
                    </span>
                @else
                    {{ $name }}
                @endif
            </div>
            <div class="col">
                @if($key === 'arguments' || ($key === 'options' && $input->acceptValue()))
                    <input type="text" name="args[{{ $name }}]" class="form-control" value="{{ $schedule?->args[$name] ?? $input->getDefault() }}" form="{{ $form }}">
                @else
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="args[{{ $name }}]" form="{{ $form }}">
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@endforeach
