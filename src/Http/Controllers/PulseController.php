<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Http\Controllers;

use Throwable;
use Exception;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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

    public function index(Request $request)
    {
        $commands = $request->has('sortKey')
            ? $this->commandService->getSorted(...$request->only(['sortKey', 'sortBy']))
            : $this->commandService->get();

        return view('pulse::index', [
            'commands' => $commands,
            //'times' => $this->service->getTimes(),
        ]);
    }

    public function edit(Request $request, Command $command)
    {
        $decorator = $this->commandService->get($command);
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

    public function update(Command $command, ScheduleSaveRequest $request)
    {
        $this->scheduleService->saveSchedule($request->validated());

        return redirect()
            ->route('pulse.edit', [
                'command' => $command,
                'schedule' => $request->get('id'),
            ]);
    }

    public function destroy(int $command, int $schedule)
    {
        Schedule::find($schedule)->delete();

        return redirect()->route('pulse.edit', $command);
    }

    public function run(int $id, ScheduleRunRequest $request)
    {
        $commandInfo = $this->service->getForClass(Command::find($id)->class);
        try {
            if (method_exists($commandInfo['object'], 'runInManual') && !$commandInfo['object']->runInManual()) {
                throw new Exception('Not use in manual running');
            }
            dispatch(new CommandRunJob($commandInfo['class'], $request->get('args', [])));
            $out = ['type' => 'success', 'message' => 'Command added in queue'];
        } catch (Throwable $e) {
            $out = ['type' => 'error', 'message' => $e->getMessage()];
        }

        return redirect()
            ->route('pulse.edit', $id)
            ->with(["{$out['type']}-message" => $out['message']]);
    }
}
