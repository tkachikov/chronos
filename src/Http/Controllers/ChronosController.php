<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Http\Controllers;

use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Tkachikov\Chronos\Actions\RealTime\GetStateAndFinishRunAction;
use Tkachikov\Chronos\Actions\RealTime\InitializeAction;
use Tkachikov\Chronos\Actions\RealTime\SendAnswerAction;
use Tkachikov\Chronos\Actions\RealTime\SigkillAction;
use Tkachikov\Chronos\Actions\RealTime\SigtermAction;
use Tkachikov\Chronos\Converters\FilterConverter;
use Tkachikov\Chronos\Converters\SortConverter;
use Tkachikov\Chronos\Dto\RealTimeDto;
use Tkachikov\Chronos\Http\Requests\IndexRequest;
use Tkachikov\Chronos\Http\Requests\ScheduleRunRequest;
use Tkachikov\Chronos\Http\Requests\ScheduleSaveRequest;
use Tkachikov\Chronos\Jobs\CommandRunJob;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Models\CommandLog;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Repositories\TimeRepositoryInterface;
use Tkachikov\Chronos\Services\CommandService;
use Tkachikov\Chronos\Services\ScheduleService;

class ChronosController extends Controller
{
    public function __construct(
        private readonly CommandService $commandService,
        private readonly ScheduleService $scheduleService,
        private readonly TimeRepositoryInterface $timeRepository,
    ) {}

    public function index(
        IndexRequest $request,
        SortConverter $sortConverter,
        FilterConverter $filterConverter,
    ): View {
        $sortDto = $sortConverter->convert($request);
        $filterDto = $filterConverter->convert($request);

        $commands = $this
            ->commandService
            ->get(
                sort: $sortDto,
                filter: $filterDto,
            );

        $times = $this
            ->timeRepository
            ->get();

        return view('chronos::index', compact(
            'commands',
            'times',
        ));
    }

    /**
     * @throws Exception
     */
    public function edit(
        Request $request,
        Command $command,
    ): View {
        $decorator = $this->commandService->getByClass($command->class);
        $schedule = $request->has('schedule')
            ? Schedule::findOrFail($request->integer('schedule'))
            : null;
        $runs = CommandRun::query()
            ->whereCommandId($command->id)
            ->orderByDesc('id')
            ->paginate(10);
        $logs = [];
        foreach ($runs as $run) {
            $logs[$run->id] = CommandLog::whereCommandRunId($run->id)->simplePaginate(5, pageName: "logs_{$run->id}");
        }

        return view('chronos::edit', [
            'command' => $decorator,
            'times' => $this
                ->timeRepository
                ->get(),
            'schedule' => $schedule,
            'runs' => $runs,
            'logs' => $logs,
        ]);
    }

    public function update(
        Command $command,
        ScheduleSaveRequest $request,
    ): RedirectResponse {
        $this->scheduleService->saveSchedule($request->validated());

        return redirect()
            ->route('chronos.edit', [
                'command' => $command,
                'schedule' => $request->get('id'),
            ]);
    }

    public function destroy(
        Command $command,
        Schedule $schedule,
    ): RedirectResponse {
        $schedule->delete();

        return redirect()->route('chronos.edit', $command);
    }

    public function run(
        Command $command,
        ScheduleRunRequest $request,
    ): RedirectResponse {
        try {
            dispatch(new CommandRunJob($command->class, $request->input('args', [])));
            $out = ['type' => 'success', 'message' => 'Command added in queue'];
        } catch (Throwable $e) {
            $out = ['type' => 'error', 'message' => $e->getMessage()];
        }

        return redirect()
            ->route('chronos.edit', $command)
            ->with(["{$out['type']}-message" => $out['message']]);
    }

    public function runInRealTime(
        Command $command,
        ScheduleRunRequest $request,
        InitializeAction $action,
    ) {
        try {
            $dto = new RealTimeDto(
                commandId: $command->id,
                args: $request->input('args', []),
                user: Auth::user(),
            );

            $action->execute($dto);
        } catch (Exception $e) {
            return response()
                ->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => 'running']);
    }

    public function getLogsForRunInRealTime(
        Command $command,
        GetStateAndFinishRunAction $action,
    ) {
        return response()->json($action->execute($command));
    }

    public function setAnswerForRunning(
        Request $request,
        Command $command,
        SendAnswerAction $action,
    ): void {
        $action->execute(
            $command,
            $request->post('answer'),
        );
    }

    public function sigterm(
        Command $command,
        SigtermAction $action,
    ): void {
        $action->execute($command);
    }

    public function sigkill(
        Command $command,
        SigkillAction $action,
    ): void {
        $action->execute($command);
    }
}
