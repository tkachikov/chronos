{{-- The arguments and the options of the command as fields of the form $form (the schedule or a run) --}}
@foreach(['arguments' => '', 'options' => '--'] as $key => $prefix)
    @foreach($command->getDefinition()->{'get'.ucfirst($key)}() as $input)
        @php
            $name = $prefix.$input->getName();
            $description = $input->getDescription();
            $default = $schedule?->args[$name] ?? $input->getDefault();
            $required = method_exists($input, 'isRequired') && $input->isRequired();
            $withValue = $key === 'arguments' || $input->acceptValue();
            $checked = is_bool($default) && $default;
            if (!$default) {
                $default = '';
            }
            $values = $input->isArray() ? (is_array($default) && $default ? array_values($default) : ['']) : [$default];
        @endphp
        <div class="cx-arg">
            <div class="cx-arg-name">
                <code class="cx-code">{{ $name }}</code>@if($required)<span class="cx-req" title="Required">*</span>@endif
                @if($description)
                    <div class="cx-arg-desc">{{ $description }}</div>
                @endif
            </div>
            <div>
                @if($withValue)
                    <div data-arg-list>
                        @foreach($values as $value)
                            <div class="cx-arg-item">
                                <input type="text"
                                       name="args[{{ $name }}]{{ $input->isArray() ? '[]' : '' }}"
                                       class="form-control"
                                       value="{{ is_array($value) ? '' : $value }}"
                                       form="{{ $form }}"
                                       @required($required)
                                />
                                @if($input->isArray())
                                    <button type="button" class="cx-btn ghost small icon" data-arg-remove title="Remove the value" aria-label="Remove the value">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14"/></svg>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if($input->isArray())
                        <button type="button" class="cx-btn ghost small cx-arg-add" data-arg-add>
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            Add a value
                        </button>
                    @endif
                @else
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               role="switch"
                               name="args[{{ $name }}]"
                               form="{{ $form }}"
                               @checked($checked)
                               @required($required)
                        />
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@endforeach
@once
    <script>
        // The values of an array argument: a value is added or removed in its own list, the last one is only cleared
        document.addEventListener('click', (event) => {
            const add = event.target.closest('[data-arg-add]');
            const remove = event.target.closest('[data-arg-remove]');

            if (add) {
                const list = add.previousElementSibling;
                const item = list.lastElementChild.cloneNode(true);
                item.querySelector('input').value = '';
                list.append(item);
                item.querySelector('input').focus();
            }

            if (remove) {
                const item = remove.closest('.cx-arg-item');
                const list = item.parentElement;
                list.children.length > 1 ? item.remove() : (item.querySelector('input').value = '');
            }
        });
    </script>
@endonce
