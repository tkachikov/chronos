<div class="card shadow mb-5">
    <div class="card-header">
        <h2 class="h2 m-0 text-muted">
            <div class="row w-100 mx-auto">
                <div class="col">Main information</div>
            </div>
        </h2>
    </div>
    <div class="card-body">
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
        @endif
        <div class="row w-100 mx-auto py-3 align-items-center">
            <div class="col-4">Short name</div>
            <div class="col-8">{{ $command->getShortName() }}</div>
        </div>
        <div class="row w-100 mx-auto py-3 align-items-center">
            <div class="col-4">Full name</div>
            <div class="col-8">{{ $command->getFullName() }}</div>
        </div>
        <div class="row w-100 mx-auto py-3 align-items-center">
            <div class="col-4">Description</div>
            <div class="col-8">{{ $command->getDescription() }}</div>
        </div>
        <div class="row w-100 mx-auto py-3 align-items-center">
            <div class="col-4">Class</div>
            <div class="col-8">{{ $command->getClassName() }}</div>
        </div>
        <div class="row w-100 mx-auto py-3 align-items-center">
            <div class="col-4">Signature</div>
            <div class="col-8">{{ $command->getSignature() }}</div>
        </div>
        <div class="row w-100 mx-auto py-3 align-items-center">
            <div class="col-4">Run in schedule</div>
            <div class="col-8">
                @if($command->runInSchedule())
                    <span class="text-success">Yes</span>
                @else
                    <span class="text-danger">No</span>
                @endif
            </div>
        </div>
        <div class="row w-100 mx-auto py-3 align-items-center">
            <div class="col-4">Run in manual</div>
            <div class="col-8">
                @if($command->runInManual())
                    <span class="text-success">Yes</span>
                @else
                    <span class="text-danger">No</span>
                @endif
            </div>
        </div>
    </div>
</div>