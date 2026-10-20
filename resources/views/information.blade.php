@php
    $definition = $command->getDefinition();
    $inputs = [
        ...array_map(fn ($input) => [$input->getName(), $input, method_exists($input, 'isRequired') && $input->isRequired()], array_values($definition->getArguments())),
        ...array_map(fn ($input) => ['--' . $input->getName(), $input, false], array_values($definition->getOptions())),
    ];
    $defaultOf = function ($input): ?string {
        $default = $input->getDefault();

        return match (true) {
            $default === null || $default === false || $default === [] || $default === '' => null,
            $default === true => 'on',
            is_array($default) => implode(', ', $default),
            default => (string) $default,
        };
    };
@endphp
<section class="cx-card cx-about">
    <div class="cx-card-head">
        <h2>About</h2>
    </div>
    <div class="cx-about-list">
        <div class="cx-about-item">
            <span class="cx-k">Class</span>
            {{-- The class breaks at the namespaces --}}
            <code class="cx-about-code">{!! implode('\\<wbr>', array_map('e', explode('\\', $command->getClassName()))) !!}</code>
        </div>
        <div class="cx-about-item">
            <span class="cx-k">Signature</span>
            <code class="cx-about-code">{{ $command->getSignature() }}</code>
        </div>
        <div class="cx-about-row">
            <div class="cx-about-item">
                <span class="cx-k">Group</span>
                @if($command->getGroupName() ?? $command->getDirectory())
                    <span>{{ $command->getGroupName() ?? $command->getDirectory() }}</span>
                @else
                    <span class="cx-none">Application</span>
                @endif
            </div>
            <div class="cx-about-item">
                <span class="cx-k">Runs</span>
                <span class="cx-about-tags">
                    @if($command->runInManual())
                        <span class="cx-tag on">Manually</span>
                    @else
                        <span class="cx-tag off" title="It can not be run from the panel">Manually: off</span>
                    @endif
                    @if($command->runInSchedule())
                        <span class="cx-tag on">By schedules</span>
                    @else
                        <span class="cx-tag off" title="It can not be scheduled">Schedules: off</span>
                    @endif
                </span>
            </div>
        </div>
        <div class="cx-about-item">
            <span class="cx-k">Arguments</span>
            @forelse($inputs as [$name, $input, $required])
                <div class="cx-arg-line">
                    <div>
                        <code class="cx-code">{{ $name }}</code>
                        @if($required)
                            <small class="cx-req">required</small>
                        @endif
                        @if($defaultOf($input) !== null)
                            <small>default {{ $defaultOf($input) }}</small>
                        @endif
                    </div>
                    @if($input->getDescription())
                        <span>{{ $input->getDescription() }}</span>
                    @endif
                </div>
            @empty
                <span class="cx-none">None</span>
            @endforelse
        </div>
    </div>
</section>
