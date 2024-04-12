@php
    use Illuminate\Support\Str;
@endphp

@foreach(['arguments' => '', 'options' => '--'] as $key => $prefix)
    @foreach($command->getDefinition()->{'get'.ucfirst($key)}() as $index => $input)
        @php
            $name = $prefix.$input->getName();
            $description = $input->getDescription();
            $default = $schedule?->args[$name] ?? $input->getDefault();
            if (!$default) {
                $default = '';
            }
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
                    <div class="row w-100 mx-auto px-0">
                        <div class="col px-0">
                            @if($input->isArray() && is_array($default) && $default)
                                @php($hashes = [])
                                @foreach($default as $defaultValue)
                                    @php($hashes[] = $hash = Str::random())
                                    <input type="text"
                                           name="args[{{ $name }}]{{ $input->isArray() ? '[]' : '' }}"
                                           class="form-control mb-2"
                                           value="{{ $defaultValue }}"
                                           form="{{ $form }}"
                                           hash="{{ $hash }}"
                                            @required($input->isRequired())
                                    />
                                @endforeach
                            @else
                                <input type="text"
                                       name="args[{{ $name }}]{{ $input->isArray() ? '[]' : '' }}"
                                       class="form-control mb-2"
                                       value="{{ $default }}"
                                       form="{{ $form }}"
                                        @required($input->isRequired())
                                />
                            @endif
                        </div>
                        @if($input->isArray())
                            <div class="col-3 px-0">
                                <div class="row w-100 mx-auto text-end">
                                    <div class="col px-0 text-center">
                                        <button class="btn btn-secondary mb-2"
                                                onclick="appendArg('{{ $form }}')">
                                            +
                                        </button>
                                        @if(is_array($default) && $default)
                                            @foreach($default as $index => $defaultValue)
                                                @if($index)
                                                    <button class="btn btn-danger mb-2"
                                                            onclick="removeArg('{{ $hashes[$index] }}')"
                                                            hash="{{ $hashes[$index] }}">
                                                        -
                                                    </button>
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <script>
                                function appendArg(form) {
                                    let element = document.querySelectorAll(`[name*=args][form=${form}]`)[0],
                                        cloneElement = element.cloneNode(true),
                                        cloneButton = event.target.cloneNode(true),
                                        hash = getHash();

                                    cloneElement.setAttribute('hash', hash);
                                    cloneElement.value = '';

                                    cloneButton.setAttribute('hash', hash);
                                    cloneButton.setAttribute('onclick', `removeArg('${hash}')`);
                                    cloneButton.setAttribute('class', 'btn btn-danger mb-2');
                                    cloneButton.textContent = '-';

                                    element.parentElement.appendChild(cloneElement);
                                    event.target.parentElement.appendChild(cloneButton);
                                }
                                function removeArg(hash) {
                                    document
                                        .querySelectorAll(`[hash='${hash}']`)
                                        .forEach((v) => v.parentElement.removeChild(v));
                                }
                                function getHash() {
                                    return (Math.random() + 1).toString(36).replace('1.', '');
                                }
                            </script>
                        @endif
                    </div>
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
