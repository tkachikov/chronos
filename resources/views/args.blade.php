@foreach(['arguments' => '', 'options' => '--'] as $key => $prefix)
    @foreach($command->getDefinition()->{'get'.ucfirst($key)}() as $input)
        @php
            $name = $prefix.$input->getName();
            $description = $input->getDescription();
            $descriptionAttributes = '';
            if ($description) {
                $descriptionAttributes = [
                    'data-bs-toggle' => 'tooltip',
                    'data-bs-title' => $description,
                    'data-bs-placement' => 'top',
                ];
                $descriptionAttributes = implode(
                    ' ',
                    array_map(
                        fn ($v, $k) => "$k=\"$v\"",
                        $descriptionAttributes,
                        array_keys($descriptionAttributes),
                    ),
                );
            }
        @endphp
        <div class="row w-100 mx-auto align-items-center">
            <div class="col">
                <span {!! $descriptionAttributes !!} class="d-inline-block">
                    @if($input->isRequired())
                        <span class="position-absolute translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                    @endif
                    {{ $name }}
                </span>
            </div>
            <div class="col my-2">
                @if($key === 'arguments' || ($key === 'options' && $input->acceptValue()))
                    <input type="text"
                           name="args[{{ $name }}]"
                           class="form-control"
                           value="{{ $schedule?->args[$name] ?? $input->getDefault() }}"
                           form="{{ $form }}"
                           @required($input->isRequired())
                    />
                @else
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               role="switch"
                               name="args[{{ $name }}]"
                               form="{{ $form }}"
                               @required($input->isRequired())
                        />
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@endforeach
