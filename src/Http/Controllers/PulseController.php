<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Http\Controllers;

use Exception;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Tkachikov\LaravelPulse\Models\Command;
use Tkachikov\LaravelPulse\Models\Schedule;
use Tkachikov\LaravelPulse\Models\CommandLog;
use Tkachikov\LaravelPulse\Models\CommandRun;
use Tkachikov\LaravelPulse\Jobs\CommandRunJob;
use Tkachikov\LaravelPulse\Services\CommandService;
use Tkachikov\LaravelPulse\Services\ScheduleService;
use Tkachikov\LaravelPulse\Http\Requests\ScheduleRunRequest;
use Tkachikov\LaravelPulse\Http\Requests\ScheduleSaveRequest;

class PulseController extends Controller
{
    public function __construct(
        private readonly CommandService $commandService,
        private readonly ScheduleService $scheduleService,
    ) {
    }

    /**
     * @param Request $request
     *
     * @throws Exception
     *
     * @return View
     */
    public function index(
        Request $request,
    ): View {
        $commands = $request->has('sortKey')
            ? $this->commandService->getSorted(...$request->only(['sortKey', 'sortBy']))
            : $this->commandService->get();

        return view('pulse::index', [
            'commands' => $commands,
            'times' => $this->commandService->getTimes(),
        ]);
    }

    /**
     * @param Request $request
     * @param Command $command
     *
     * @throws Exception
     *
     * @return View
     */
    public function edit(
        Request $request,
        Command $command,
    ): View {
        $decorator = $this->commandService->get($command->class);
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

        return view('pulse::edit', [
            'command' => $decorator,
            'times' => $this->commandService->getTimes(),
            'schedule' => $schedule,
            'runs' => $runs,
            'logs' => $logs,
        ]);
    }

    /**
     * @param Command             $command
     * @param ScheduleSaveRequest $request
     *
     * @return RedirectResponse
     */
    public function update(
        Command $command,
        ScheduleSaveRequest $request,
    ): RedirectResponse {
        $this->scheduleService->saveSchedule($request->validated());

        return redirect()
            ->route('pulse.edit', [
                'command' => $command,
                'schedule' => $request->get('id'),
            ]);
    }

    /**
     * @param Command  $command
     * @param Schedule $schedule
     *
     * @return RedirectResponse
     */
    public function destroy(
        Command $command,
        Schedule $schedule,
    ): RedirectResponse {
        $schedule->delete();

        return redirect()->route('pulse.edit', $command);
    }

    /**
     * @param Command            $command
     * @param ScheduleRunRequest $request
     *
     * @return RedirectResponse
     */
    public function run(
        Command $command,
        ScheduleRunRequest $request,
    ): RedirectResponse {
        try {
            dispatch(new CommandRunJob($command->class, $request->get('args', [])));
            $out = ['type' => 'success', 'message' => 'Command added in queue'];
        } catch (Throwable $e) {
            $out = ['type' => 'error', 'message' => $e->getMessage()];
        }

        return redirect()
            ->route('pulse.edit', $command)
            ->with(["{$out['type']}-message" => $out['message']]);
    }
}
