{{-- The buttons of the runs in the head of the command and their windows --}}
@if($command->runInManual())
    @php
        $hasArgs = filled($command->getDefinition()->getArguments()) || filled($command->getDefinition()->getOptions());
    @endphp
    <button type="button" class="cx-btn primary" data-bs-toggle="modal" data-bs-target="#runCommandInRealTimeModal">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="m7 9 3 3-3 3M12 15h5"/></svg>
        Run in real time
    </button>
    @if($hasArgs)
        <button type="button" class="cx-btn" data-bs-toggle="modal" data-bs-target="#runCommandModal">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M7 5v14l11-7z"/></svg>
            Run from queue
        </button>
    @else
        <button type="submit" class="cx-btn" form="runCommand">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M7 5v14l11-7z"/></svg>
            Run from queue
        </button>
    @endif
    <form id="runCommand" method="POST" action="{{ route('chronos.run', $command->getModel()) }}">
        @csrf
    </form>

    <div class="modal fade" id="runCommandModal" tabindex="-1" aria-labelledby="runCommandModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <span id="runCommandModalLabel">Run from queue</span>
                    <button type="button" data-bs-dismiss="modal" class="btn-close" aria-label="Close"></button>
                </div>
                <div class="modal-body cx-args">
                    @include('chronos::args', ['command' => $command, 'form' => 'runCommand'])
                </div>
                <div class="modal-footer">
                    <button type="button" data-bs-dismiss="modal" class="cx-btn ghost">Cancel</button>
                    <button type="submit" class="cx-btn primary" form="runCommand">Run</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="runCommandInRealTimeModal" tabindex="-1" aria-labelledby="runCommandInRealTimeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <span id="runCommandInRealTimeModalLabel">Run in real time</span>
                    <button type="button" data-bs-dismiss="modal" class="btn-close" aria-label="Close"></button>
                </div>
                <div class="modal-body cx-args">
                    @if($hasArgs)
                        <div class="cx-rt-args">
                            @include('chronos::args', ['command' => $command, 'form' => 'runCommandInRealTime'])
                        </div>
                    @endif
                    <div id="terminal" class="cx-terminal"></div>
                </div>
                <div class="modal-footer">
                    <span id="runMessageError" class="cx-rt-error"></span>
                    <button type="button" data-bs-dismiss="modal" class="cx-btn ghost">Close</button>
                    <button id="sigterm" class="cx-btn warning" type="button" onclick="sigterm()">SIGTERM</button>
                    <button id="sigkill" class="cx-btn danger" type="button" onclick="sigkill()">SIGKILL</button>
                    <button id="runCommandInRealTime" class="cx-btn primary" type="button" onclick="runRealTime()">Run</button>
                </div>
            </div>
        </div>
    </div>
@endif
