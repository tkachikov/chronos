<div class="card shadow">
    <div class="card-header">
        <h2 class="h2 m-0 text-muted">
            Statistics
        </h2>
    </div>
    <div class="card-body">
        @if($command->getModel()->metrics()->count())
            <table class="table m-0 text-center table-bordered">
                <thead>
                <tr>
                    <th colspan="3">Time</th>
                    <th colspan="3">Memory</th>
                </tr>
                <tr>
                    <th>AVG</th>
                    <th>MIN</th>
                    <th>MAX</th>
                    <th>AVG</th>
                    <th>MIN</th>
                    <th>MAX</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    @foreach(['time', 'memory'] as $type)
                        @foreach(['avg', 'min', 'max'] as $key)
                            <td>{{ $command->getModel()->metrics->{$type.'_'.$key} ?? '' }}</td>
                        @endforeach
                    @endforeach
                </tr>
                </tbody>
            </table>
        @else
            Not found statistics
        @endif
    </div>
</div>