<div class="card shadow">
    <div class="card-header">
        <div class="row w-100 mx-auto">
            <div class="col">
                <h2 class="h2 m-0 text-muted">Runs</h2>
            </div>
        </div>
    </div>
    <div class="card-body">
        <table class="table m-0">
            <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Exec (sec)</th>
                <th>Memory</th>
                <th>Args</th>
                <th>State</th>
                <th class="w-50">Logs</th>
            </tr>
            </thead>
            <tbody>
            @if(!$runs->count())
                <tr>
                    <td colspan="5" @class(['border-bottom-0' => true])>No runs</td>
                </tr>
            @endif
            @foreach($runs as $run)
                @php($border = ['border-bottom-0' => $loop->last])
                <tr>
                    <td @class($border)>{{ $run->id }}</td>
                    <td @class($border)>{{ $run->user?->email ?? $run->user?->id }}</td>
                    <td @class($border)>{{ $run->created_at->diffInSeconds($run->updated_at) }}</td>
                    <td @class($border)>{{ $run->memory }}</td>
                    <td @class($border)>
                        @if($run->args)
                            {{ $command->getNameWithArguments($run->args) }}
                        @endif
                    </td>
                    <td @class($border)>
                        <span class="text-{{ $run->stateCss }}">
                            {{ $run->stateTitle }}
                        </span>
                    </td>
                    <td @class($border)>
                        <table class="table m-0">
                            <tbody>
                            @foreach($logs[$run->id] as $log)
                                @php($borderLog = ['border-bottom-0' => $loop->last])
                                <tr>
                                    <td @class($borderLog)>
                                        @if($log->type->css())
                                            <span class="border-3 border-start border-{{ $log->type->css() }} px-3">
                                                {{ $log->message }}
                                            </span>
                                        @else
                                            <div>
                                                {!! $log->message !!}
                                                <style>
                                                    .sf-dump {
                                                        margin: 0!important;
                                                        z-index: 0!important;
                                                    }
                                                </style>
                                            </div>
                                        @endif
                                    </td>
                                    <td @class($borderLog)>{{ $log->created_at }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <div class="row w-100 mx-auto">
                            <div class="col">
                                {{ $logs[$run->id]->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="row w-100 mx-auto">
            <div class="col">
                {{ $runs->appends(request()->query())->links('chronos::pagination') }}
            </div>
        </div>
    </div>
</div>