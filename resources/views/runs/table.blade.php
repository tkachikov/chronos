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
                <th>Date</th>
                <th>Exec (sec)</th>
                <th>Memory</th>
                <th>Schedule ID</th>
                <th>Telescope</th>
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
                    <td @class($border)>{{ $run->created_at }}</td>
                    <td @class($border)>{{ $run->created_at->diffInSeconds($run->updated_at) }}</td>
                    <td @class($border)>{{ $run->memory }}</td>
                    <td @class($border)>{{ $run->schedule_id }}</td>
                    <td @class($border)>
                        @if($run->telescope_id)
                            @if(config('telescope.enabled'))
                                <a href="{{ route('telescope', "commands/{$run->telescope_id}") }}" target="_blank">link</a>
                            @else
                                <span class="text-danger">Off</span>
                            @endif
                        @else
                            no link
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
                                    <td @class(array_merge($borderLog, ['w-75' => true]))>
                                                                    <span class="border-3 border-start border-{{ $log->type->css() }} px-3">
                                                                        {{ $log->message }}
                                                                    </span>
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