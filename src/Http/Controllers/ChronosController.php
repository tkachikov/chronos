<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Http\Controllers;

use Exception;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Http\RedirectResponse;
use Tkachikov\Chronos\Converters\FilterConverter;
use Tkachikov\Chronos\Converters\SortConverter;
use Tkachikov\Chronos\Http\Requests\IndexRequest;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Models\CommandLog;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Jobs\CommandRunJob;
use Tkachikov\Chronos\Services\ChronosRealTimeRunner;
use Tkachikov\Chronos\Services\CommandService;
use Tkachikov\Chronos\Services\ScheduleService;
use Tkachikov\Chronos\Http\Requests\ScheduleRunRequest;
use Tkachikov\Chronos\Http\Requests\ScheduleSaveRequest;

class ChronosController extends Controller
{
    public function __construct(
        private readonly CommandService $commandService,
        private readonly ScheduleService $scheduleService,
        private readonly ChronosRealTimeRunner $chronosRealTimeRunner,
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

        return view('chronos::index', [
            'commands' => $commands,
            'times' => $this->commandService->getTimes(),
        ]);
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
            'times' => $this->commandService->getTimes(),
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
    ) {
        $uuid = null;
        $message = null;
        
        try {
            $uuid = $this
                ->chronosRealTimeRunner
                ->initRun($command, $request->input('args', []));
        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        return response()->json(['uuid' => $uuid, 'message' => $message]);
    }

    public function getLogsForRunInRealTime(
        Command $command,
        string $uuid,
    ) {
        return response()->json($this->chronosRealTimeRunner->getLogs($command, $uuid));
    }

    public function setAnswerForRunning(Request $request, Command $command, string $uuid)
    {
        $this->chronosRealTimeRunner->setAnswer($command, $uuid, $request->string('answer')->toString());
    }

    public function sigterm(Command $command, string $uuid): void
    {
        $this
            ->chronosRealTimeRunner
            ->sigterm($command, $uuid);
    }

    public function sigkill(Command $command, string $uuid): void
    {
        $this
            ->chronosRealTimeRunner
            ->sigkill($command, $uuid);
    }
}
