<div class="card shadow mb-5">
    <div class="card-header">
        <h2 class="h2 m-0 text-muted">Schedules</h2>
    </div>
    <div class="card-body">
        <table class="table m-0">
            <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Run</th>
                <th>With overlapping</th>
                <th>Run in background</th>
                <th>Time</th>
                <th>In code</th>
                <th>Edit</th>
            </tr>
            </thead>
            <tbody>
            @if(!$command->getModel()->schedules->count())
                <tr>
                    <td colspan="8" @class(['border-bottom-0' => true])>No schedules</td>
                </tr>
            @endif
            @foreach($command->getModel()->schedules as $item)
                @php
                    $border = ['border-bottom-0' => true];
                    $args = !$item->args ? '' : str(json_encode($item->preparedArgs))
                        ->replace('{', '[')
                        ->replace('}', ']')
                        ->replace(':', ' => ')
                        ->replace('"', "'")
                        ->toString();
                @endphp
                <tr @class(['bg-light' => $item->id == $schedule?->id])>
                    <td @class($border)>{{ $item->id }}</td>
                    <td @class($border)>
                        @if($item->user_id && ($user = $item->userWithTrashed()->first()))
                            {{ $user->email }}
                        @endif
                    </td>
                    @foreach(['run', 'without_overlapping', 'run_in_background'] as $key)
                        <td @class($border)>
                                                    <span class="text-{{ $item->{$key} ? 'success' : 'danger' }}">
                                                        {{ $item->{$key} ? 'On' : 'Off' }}
                                                    </span>
                        </td>
                    @endforeach
                    <td @class($border)>
                        {{ $times[$item->time_method]['title'] }}
                        @if($item->time_params)
                            @if(count($item->time_params) === 1)
                                {{ $item->time_params[0] }}
                            @else
                                @foreach($times[$item->time_method]['params'] as $key => $param)
                                    <div class="row w-100 mx-auto">
                                        <div class="px-0">
                                            {{ $param['name'] }}: {{ $item->time_params[$key] }}
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        @endif
                    </td>
                    <td @class($border)>
                        <div class="d-none d-xl-block">@include('chronos::schedules.in_code', ['item' => $item, 'command' => $command])</div>
                        <div class="d-block d-xl-none">
                            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#inCodeViewModal">
                                Open
                            </button>
                            <div class="modal fade" id="inCodeViewModal" tabindex="-1" aria-labelledby="inCodeViewLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            In code
                                            <button type="button" data-bs-dismiss="modal" class="btn-close" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
@include('chronos::schedules.in_code', ['item' => $item, 'command' => $command])
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td @class($border)>
                        <div class="row w-100 mx-auto">
                            <div class="col">
                                <a href="{{ route('chronos.edit', ['command' => $command->getModel(), 'schedule' => $item->id]) }}">
                                    @include('chronos::icons.edit')
                                </a>
                            </div>
                            <div class="col">
                                <a data-bs-toggle="modal" data-bs-target="#deleteModal_{{ $item->id }}" class="text-danger">
                                    @include('chronos::icons.bucket')
                                </a>
                                @include('chronos::delete-modal', ['id' => 'deleteModal_'.$item->id, 'action' => route('chronos.schedules.destroy', ['command' => $command->getModel(), 'schedule' => $item->id])])
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>