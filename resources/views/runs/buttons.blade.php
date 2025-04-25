@if($command->runInManual())
    <div class="row w-100 mx-auto py-3 align-items-center">
        <div class="col text-center">
            <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#runCommandInRealTimeModal">
                Run in real time
            </button>
            @if(
                empty($command->getDefinition()->getArguments())
                && empty($command->getDefinition()->getOptions())
            )
                <button type="submit" class="btn btn-success" form="runCommand">
                    Run from queue
                </button>
            @else
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#runCommandModal">
                    Run from queue
                </button>
            @endif
        </div>
    </div>
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