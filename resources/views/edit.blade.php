@inject('scheduleMonitor', 'Tkachikov\Chronos\Services\ScheduleMonitorService')
@php
    use Illuminate\Support\Js;
    use Illuminate\Support\Str;
    use Tkachikov\Chronos\Enums\ScheduleStatusEnum;
    use Tkachikov\Chronos\Services\ScheduleMonitorService;

    $model = $command->getModel();
    $lastRun = $model->lastRun;
    $metrics = $model->metrics;
    $schedules = $model->schedules;
    $group = $command->getGroupName() ?? $command->getDirectory();
    $editing = (bool) $schedule?->id;
    // The form of a schedule is open when editing, adding or after errors
    $formOpen = $editing || request()->has('new') || (isset($errors) && $errors->any());
    $runsFilter ??= '';
    $runCounts ??= ['all' => $runs->total(), 'failed' => 0, 'running' => 0];
    $states = [
        0 => ['success', 'Succeeded'],
        1 => ['failed', 'Failed'],
        2 => ['running', 'Running'],
        3 => ['killed', 'Killed'],
    ];
    [$state, $stateTitle] = $states[$lastRun?->state] ?? [null, 'Never run'];
    $seconds = function ($value): string {
        if ($value === null || $value === '') {
            return '—';
        }
        $value = (int) round((float) $value);

        return match (true) {
            $value < 60 => $value . 's',
            $value < 3600 => intdiv($value, 60) . 'm ' . str_pad((string) ($value % 60), 2, '0', STR_PAD_LEFT) . 's',
            default => intdiv($value, 3600) . 'h ' . str_pad((string) intdiv($value % 3600, 60), 2, '0', STR_PAD_LEFT) . 'm',
        };
    };
    $range = fn ($min, $max): string => $min === $max ? (string) $min : "{$min} – {$max}";
    $duration = fn ($run): int => (int) $run->state === 2
        ? $run->created_at->diffInSeconds(now())
        : $run->created_at->diffInSeconds($run->updated_at);

    // The nearest run among the schedules that are on
    $next = $schedules
        ->where('run', true)
        ->map(fn ($item) => ['at' => $scheduleMonitor->getNextRunAt($item), 'schedule' => $item])
        ->filter(fn ($item) => $item['at'] !== null)
        ->sortBy('at')
        ->first();

    // What needs attention: what happened, what to do, where to look
    $failing = in_array((int) $lastRun?->state, [1, 3], true) && $lastRun;
    $attention = [];
    if ($failing) {
        $attention[] = [
            'level' => 'bad',
            'text' => 'The last run ' . ((int) $lastRun->state === 3 ? 'was killed' : 'failed') . ' ' . $lastRun->created_at->diffForHumans() . '.',
            'hint' => 'Its output is in the runs below.',
            'href' => '#run-' . $lastRun->id,
            'link' => 'Open the run',
        ];
    }
    foreach ($schedules as $item) {
        $name = $item->name ?: 'Schedule #' . $item->id;
        if ($scheduleMonitor->getLockState($item) === ScheduleMonitorService::LOCK_STUCK) {
            $attention[] = [
                'level' => 'bad',
                'text' => "The lock of “{$name}” is stuck: {$item->skipped_count} runs were skipped, but nothing is running.",
                'hint' => 'Release it by',
                'code' => 'php artisan schedule:clear-cache',
            ];
        }
        if ($missed = $scheduleMonitor->getMissedSince($item)) {
            $attention[] = [
                'level' => 'warn',
                'text' => "“{$name}” was due at {$missed->toDateTimeString()} and has not started.",
                'hint' => 'Check that the scheduler is running.',
            ];
        }
        if (!$failing && $item->last_status === ScheduleStatusEnum::FAILED->value) {
            $attention[] = [
                'level' => 'bad',
                'text' => "The last run of “{$name}” failed" . ($item->last_run_at ? ' at ' . $item->last_run_at->toDateTimeString() : '') . '.',
                'hint' => 'Its output is in the runs below.',
                'href' => '#runs',
                'link' => 'Open the runs',
            ];
        }
    }
    if ($command->notRunInManual() && $schedules->where('run', true)->isEmpty()) {
        $attention[] = [
            'level' => 'info',
            'text' => 'This command is not run anywhere: manual runs are off and no schedule is on.',
            'hint' => $command->notRunInSchedule() ? 'Schedules are off for it too.' : 'Add a schedule to run it.',
        ];
    }
@endphp
@extends('chronos::layout', ['title' => $command->getShortName()])

@section('styles')
    <style>
        /* The head: the way back, the name and the buttons of the runs */
        .cx-page-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; flex-wrap: wrap; margin-bottom: 20px; }
        .cx-crumbs { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--cx-faint); margin-bottom: 6px; }
        .cx-crumbs a { color: var(--cx-muted); text-decoration: none; font-weight: 500; }
        .cx-crumbs a:hover { color: var(--cx-primary); }
        .cx-page-head h1 { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin: 0; font-size: 26px; font-weight: 700; letter-spacing: -.01em; }
        .cx-page-head h1 .cx-code { font-size: 13px; font-weight: 500; padding: 2px 8px; }
        .cx-page-desc { margin: 6px 0 0; max-width: 720px; color: var(--cx-muted); font-size: 14px; }
        .cx-page-tags { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
        .cx-page-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        /* The overview: the last run, the next one, the runs, the averages */
        .cx-overview { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
        .cx-tile { display: block; min-width: 0; padding: 14px 16px; background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 14px; box-shadow: var(--cx-shadow); color: var(--cx-text); text-decoration: none; }
        a.cx-tile:hover { border-color: var(--cx-faint); color: var(--cx-text); }
        .cx-tile .cx-k { margin-bottom: 6px; }
        .cx-tile-v { display: flex; align-items: center; gap: 8px; font-size: 18px; font-weight: 700; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cx-tile-v .cx-dot { width: 9px; height: 9px; box-shadow: none; }
        .cx-tile-v.success { color: var(--cx-success); }
        .cx-tile-v.failed, .cx-tile-v.killed { color: var(--cx-danger); }
        .cx-tile-v.running { color: var(--cx-info); }
        .cx-tile-v.muted { color: var(--cx-muted); font-weight: 600; }
        .cx-tile-v i { font-style: normal; color: var(--cx-faint); font-weight: 400; }
        .cx-tile small { display: block; margin-top: 4px; font-size: 12.5px; color: var(--cx-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cx-tile small .bad { color: var(--cx-danger); font-weight: 600; }
        .cx-tile small .active { color: var(--cx-info); font-weight: 600; }

        /* What needs attention */
        .cx-attention { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
        .cx-issue { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; font-size: 14px; }
        .cx-issue.bad { background: var(--cx-danger-soft); color: var(--cx-danger); }
        .cx-issue.warn { background: var(--cx-warning-soft); color: var(--cx-warning); }
        .cx-issue.info { background: var(--cx-primary-soft); color: var(--cx-info); }
        .cx-issue svg { flex: none; }
        .cx-issue > div { flex: 1; min-width: 0; }
        .cx-issue b { font-weight: 600; }
        .cx-issue span { color: var(--cx-text); opacity: .75; }
        .cx-issue .cx-code { color: var(--cx-text); margin-left: 4px; }
        .cx-issue .cx-btn { flex: none; color: inherit; border-color: transparent; background: rgba(255, 255, 255, .6); }
        [data-bs-theme="dark"] .cx-issue .cx-btn { background: rgba(0, 0, 0, .25); }
        .cx-issue .cx-btn:hover { background: var(--cx-surface); }

        .cx-page { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 20px; align-items: start; }
        .cx-main, .cx-aside { display: flex; flex-direction: column; gap: 20px; min-width: 0; }

        .cx-k { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--cx-faint); margin-bottom: 2px; }
        .cx-empty-note { color: var(--cx-muted); font-size: 14px; }
        .cx-empty-note .cx-btn { margin-top: 10px; }

        /* Schedules: a row each; the form opens in the list */
        .cx-sched-cols, .cx-sched { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1.1fr) minmax(0, 1.1fr) auto; gap: 16px 20px; align-items: center; padding: 14px 20px; }
        .cx-sched-cols { padding-top: 10px; padding-bottom: 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--cx-faint); background: var(--cx-surface-2); border-bottom: 1px solid var(--cx-border); }
        .cx-sched { border-top: 1px solid var(--cx-border); }
        .cx-sched-cols + .cx-sched { border-top: 0; }
        .cx-sched.active { background: var(--cx-primary-soft); }
        .cx-sched-main { display: flex; gap: 12px; align-items: flex-start; min-width: 0; }
        .cx-sched-main .cx-switch { margin-top: 6px; }
        .cx-sched-text { min-width: 0; }
        .cx-sched-title { display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap; }
        .cx-sched-title b { font-weight: 600; }
        .cx-sched.off .cx-sched-title b { color: var(--cx-muted); }
        .cx-sched-id { font-size: 12px; color: var(--cx-faint); }
        .cx-sched-time { margin-top: 2px; font-size: 13px; color: var(--cx-muted); }
        .cx-sched-time .cx-code { margin-left: 4px; }
        .cx-sched-flags { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px; }
        .cx-sched-run { font-size: 13px; min-width: 0; }
        .cx-sched-run .cx-k { margin-bottom: 4px; }
        .cx-sched-run .badge { font-weight: 600; white-space: normal; text-align: left; margin-top: 4px; }
        .cx-sched-run small.rel { display: block; color: var(--cx-muted); }
        .cx-sched-actions { display: flex; gap: 2px; justify-content: flex-end; }
        .cx-sched-actions .cx-btn.del:hover { color: var(--cx-danger); background: var(--cx-danger-soft); }
        .cx-sched-actions .cx-btn[aria-expanded="true"], .cx-sched-actions .cx-btn.current { color: var(--cx-primary); background: var(--cx-primary-soft); }
        .cx-sched-code { grid-column: 1 / -1; }
        .cx-sched-code pre { margin: 0; padding: 12px 14px; border-radius: 10px; background: var(--cx-terminal); color: #e6edf3; font-size: 12.5px; line-height: 1.5; overflow-x: auto; }
        .cx-btn-text { display: none; }

        /* The form of a schedule in its window */
        #scheduleModal .modal-header { gap: 10px; }
        .cx-modal-sub { font-weight: 400; color: var(--cx-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        #scheduleModal .modal-body { background: var(--cx-surface-2); }
        .cx-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 20px; }
        .cx-field { min-width: 0; }
        .cx-field.wide { grid-column: 1 / -1; }
        .cx-field > label, .cx-field > .cx-label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: var(--cx-muted); }
        .cx-field-hint { display: block; margin-top: 6px; font-size: 12px; color: var(--cx-faint); }
        .cx-params { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .cx-params label { display: block; font-size: 12px; color: var(--cx-muted); margin-bottom: 4px; }
        .cx-params > div { display: flex; gap: 10px; flex-wrap: wrap; width: 100%; }
        .cx-params > div > div { flex: 1 1 120px; }
        .cx-switches { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .cx-switch-box { padding: 12px 14px; border: 1px solid var(--cx-border); border-radius: 10px; background: var(--cx-surface); }
        .cx-switch-box.on { border-color: var(--cx-primary); }
        .cx-switch-row { display: flex; align-items: center; gap: 10px; padding: 0; min-height: 0; }
        .cx-switch-row .form-check-input { flex: none; margin: 0; }
        .cx-switch-row label { display: flex; align-items: baseline; gap: 6px; min-width: 0; font-size: 14px; cursor: pointer; }
        .cx-switch-row label b { font-weight: 600; }
        .cx-switch-row label span { font-size: 12px; color: var(--cx-faint); }
        .cx-switch-box small { display: block; margin-top: 6px; font-size: 12px; color: var(--cx-faint); line-height: 1.4; }
        .cx-minutes { display: flex; align-items: center; gap: 8px; margin-top: 10px; font-size: 12px; color: var(--cx-muted); }
        .cx-minutes input { width: 88px; min-height: 34px; padding: 4px 8px; }
        .cx-errors { margin-bottom: 14px; padding: 10px 14px; border-radius: 10px; background: var(--cx-danger-soft); color: var(--cx-danger); font-size: 13px; }
        .cx-errors ul { margin: 0; padding-left: 18px; }

        /* The arguments of a command: the form and the runs */
        .cx-arg { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr); gap: 4px 12px; align-items: start; }
        .cx-arg + .cx-arg { margin-top: 12px; }
        .cx-arg-name { padding-top: 8px; min-width: 0; }
        .cx-arg-name .cx-code { color: var(--cx-text); }
        .cx-req { color: var(--cx-danger); font-weight: 700; margin-left: 2px; }
        .cx-arg-desc { margin-top: 4px; font-size: 12px; color: var(--cx-faint); }
        .cx-arg-item { display: flex; gap: 6px; align-items: center; }
        .cx-arg-item + .cx-arg-item { margin-top: 6px; }
        .cx-arg-item .cx-btn { flex: none; }
        .cx-arg-add { margin-top: 6px; }
        .cx-arg .form-switch { display: flex; align-items: center; min-height: 38px; margin: 0; padding: 0; }
        .cx-arg .form-switch .form-check-input { margin-left: 0; }
        .modal .cx-args { text-align: left; }
        #scheduleModal .cx-arg { grid-template-columns: 220px minmax(0, 1fr); max-width: 640px; }

        /* Runs: a line each, the output opens under it */
        .cx-run-filters { margin-left: auto; display: flex; gap: 2px; }
        .cx-run-filters a { display: inline-flex; align-items: center; gap: 6px; height: 28px; padding: 0 10px; border-radius: 8px; color: var(--cx-muted); font-size: 13px; font-weight: 500; text-decoration: none; white-space: nowrap; }
        .cx-run-filters a:hover { color: var(--cx-text); background: var(--cx-surface-2); }
        .cx-run-filters a.active { color: var(--cx-primary); background: var(--cx-primary-soft); font-weight: 600; }
        .cx-run-filters a .cx-n { color: var(--cx-faint); background: transparent; padding: 0; min-width: 0; }
        .cx-run-filters a.active .cx-n { color: var(--cx-primary); }
        .cx-run-filters a.bad .cx-n { color: var(--cx-danger); }
        .cx-run { border-top: 1px solid var(--cx-border); }
        .cx-run:first-of-type { border-top: 0; }
        .cx-run-head { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 12px 20px; font-size: 13px; cursor: pointer; list-style: none; }
        .cx-run-head::-webkit-details-marker { display: none; }
        .cx-run-head:hover { background: var(--cx-surface-2); }
        .cx-run[open] > .cx-run-head { background: var(--cx-surface-2); }
        .cx-run-head .cx-dot { width: 8px; height: 8px; box-shadow: none; }
        .cx-run-state { font-weight: 600; }
        .cx-run-state.success { color: var(--cx-success); }
        .cx-run-state.failed, .cx-run-state.killed { color: var(--cx-danger); }
        .cx-run-state.running { color: var(--cx-info); }
        .cx-run-id { color: var(--cx-faint); }
        .cx-run-head time { color: var(--cx-muted); }
        .cx-run-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-left: auto; color: var(--cx-muted); }
        .cx-run-meta span { display: inline-flex; align-items: center; gap: 4px; }
        .cx-run-meta svg { color: var(--cx-faint); }
        .cx-run-chevron { flex: none; color: var(--cx-faint); transition: transform .15s; }
        .cx-run[open] > .cx-run-head .cx-run-chevron { transform: rotate(90deg); }
        .cx-run-args { flex-basis: 100%; margin-left: 18px; }
        .cx-logs { padding: 10px 20px 14px 38px; }
        .cx-log { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; padding: 4px 0 4px 12px; border-left: 3px solid var(--cx-border); font-size: 13px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
        .cx-log + .cx-log { margin-top: 4px; }
        .cx-log.primary { border-left-color: var(--cx-info); }
        .cx-log.warning { border-left-color: var(--cx-warning); }
        .cx-log.danger { border-left-color: var(--cx-danger); color: var(--cx-danger); }
        .cx-log-msg { white-space: pre-wrap; overflow-wrap: anywhere; min-width: 0; }
        .cx-log-dump { min-width: 0; overflow-x: auto; }
        .cx-log time { flex: none; font-size: 12px; color: var(--cx-faint); font-family: inherit; }
        .cx-log-pages { display: flex; align-items: center; gap: 8px; margin-top: 8px; font-size: 12px; color: var(--cx-muted); }
        .cx-card-foot { padding: 12px 20px; border-top: 1px solid var(--cx-border); }
        .sf-dump { margin: 0 !important; z-index: 0 !important; }

        /* About: a label over each value, the code on its own lines */
        .cx-about-list { display: flex; flex-direction: column; gap: 16px; padding: 16px 20px; font-size: 14px; }
        .cx-about-item { min-width: 0; }
        .cx-about-item .cx-k { margin-bottom: 6px; }
        .cx-about-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr); gap: 16px; }
        .cx-about-code { display: block; padding: 8px 10px; border-radius: 8px; background: var(--cx-surface-2); border: 1px solid var(--cx-border); color: var(--cx-text); font-size: 12.5px; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; }
        .cx-about-tags { display: flex; gap: 6px; flex-wrap: wrap; }
        .cx-arg-line { display: flex; flex-direction: column; gap: 2px; padding: 8px 0; border-top: 1px solid var(--cx-border); font-size: 13px; }
        .cx-arg-line:first-of-type { border-top: 0; padding-top: 0; }
        .cx-arg-line:last-child { padding-bottom: 0; }
        .cx-arg-line > div { display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap; }
        .cx-arg-line .cx-code { color: var(--cx-text); }
        .cx-arg-line span { color: var(--cx-muted); }
        .cx-arg-line small { color: var(--cx-faint); font-size: 12px; }

        /* Running in real time */
        .cx-rt-args { margin-bottom: 16px; max-width: 720px; }
        .cx-rt-args .cx-arg { grid-template-columns: 200px minmax(0, 1fr); }
        .cx-terminal { width: 100%; height: min(60vh, 560px); padding: 12px 14px; border-radius: 10px; background: var(--cx-terminal); color: #e6edf3; overflow: auto; font: 13px/1.5 ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
        .cx-terminal:empty::before { content: "The output appears here"; color: #6e7681; }
        .cx-terminal pre { margin: 0; white-space: pre-wrap; overflow-wrap: anywhere; font: inherit; color: inherit; }
        .cx-terminal .entering { outline: 0; margin-left: 10px; border-bottom: 1px dashed #6e7681; min-width: 40px; display: inline-block; }
        .cx-rt-error { margin-right: auto; font-size: 13px; color: var(--cx-danger); }

        @media (max-width: 1100px) {
            .cx-page { grid-template-columns: minmax(0, 1fr); }
            .cx-main, .cx-aside { display: contents; }
            .cx-schedules { order: 1; }
            .cx-runs { order: 2; }
            .cx-about { order: 3; }
            .cx-overview { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 768px) {
            .cx-page { gap: 16px; }
            .cx-page-head { margin-bottom: 16px; gap: 12px; }
            .cx-page-head h1 { font-size: 22px; }
            .cx-page-actions { width: 100%; }
            .cx-page-actions .cx-btn { flex: 1; }
            .cx-overview { gap: 8px; }
            .cx-tile { padding: 12px 14px; border-radius: 12px; }
            .cx-tile-v { font-size: 16px; white-space: normal; }
            .cx-tile small { white-space: normal; }
            .cx-issue { align-items: flex-start; flex-wrap: wrap; padding: 12px 14px; }
            .cx-issue > div { flex-basis: calc(100% - 30px); }
            .cx-issue .cx-btn { margin-left: 30px; }
            .cx-sched-cols { display: none; }
            .cx-sched { grid-template-columns: minmax(0, 1fr); gap: 12px; padding: 14px 16px; }
            .cx-sched-run { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
            .cx-sched-run .cx-k { flex: none; width: 68px; margin: 0; }
            .cx-sched-run > div, .cx-sched-run > span:not(.cx-k) { min-width: 0; }
            .cx-sched-run + .cx-sched-run { margin-top: -6px; }
            .cx-sched-actions { justify-content: flex-start; margin: -4px 0 0 -8px; }
            .cx-sched-actions .cx-btn { width: auto; padding: 0 10px; gap: 6px; }
            .cx-sched-actions .cx-btn .cx-btn-text { display: inline; }
            .cx-fields { grid-template-columns: minmax(0, 1fr); }
            .cx-switches { grid-template-columns: minmax(0, 1fr); }
            #scheduleModal .cx-arg, .cx-arg, .cx-rt-args .cx-arg { grid-template-columns: minmax(0, 1fr); max-width: none; }
            .cx-arg-name { padding-top: 0; }
            .cx-card-head { flex-wrap: wrap; }
            .cx-run-filters { margin-left: 0; flex-basis: 100%; }
            .cx-run-head { position: relative; padding: 12px 40px 12px 16px; }
            .cx-run-chevron { position: absolute; right: 14px; top: 13px; }
            .cx-run-meta { margin-left: 0; flex-basis: 100%; }
            .cx-run-args { margin-left: 0; }
            .cx-logs { padding: 10px 16px 12px; }
            .cx-log { flex-direction: column; gap: 2px; }
            .cx-card-foot { padding: 12px 16px; }
            .cx-about-list { padding: 14px 16px; }
            .cx-terminal { height: 50vh; }
        }
    </style>
@endsection

@section('content')
    <form id="updateForm" method="POST" action="{{ route('chronos.update', $model) }}">
        @csrf
        <input type="hidden" name="command_id" value="{{ $model->id }}">
        @if($editing)
            <input type="hidden" name="id" value="{{ $schedule->id }}">
        @endif
    </form>

    <div class="cx-page-head">
        <div style="min-width: 0">
            <nav class="cx-crumbs" aria-label="Breadcrumb">
                <a href="{{ route('chronos.main') }}">Commands</a>
                @if($group)
                    <span>/</span>
                    <a href="{{ route('chronos.main', ['group' => Str::slug($group)]) }}">{{ $group }}</a>
                @endif
            </nav>
            <h1>
                {{ $command->getShortName() }}
                <code class="cx-code">{{ $command->getName() }}</code>
            </h1>
            @if($command->getDescription())
                <p class="cx-page-desc">{{ $command->getDescription() }}</p>
            @endif
            @if($command->notRunInManual() || $command->notRunInSchedule())
                <div class="cx-page-tags">
                    @if($command->notRunInManual())
                        <span class="cx-tag off" title="It can not be run from the panel">Manual runs off</span>
                    @endif
                    @if($command->notRunInSchedule())
                        <span class="cx-tag off" title="It can not be scheduled">Schedules off</span>
                    @endif
                </div>
            @endif
        </div>
        <div class="cx-page-actions">
            @include('chronos::runs.buttons', ['command' => $command])
        </div>
    </div>

    <div class="cx-overview">
        <a class="cx-tile" href="#runs">
            <span class="cx-k">Last run</span>
            @if($lastRun)
                <b class="cx-tile-v {{ $state }}"><span class="cx-dot {{ $state }}"></span>{{ $stateTitle }}</b>
                <small title="{{ $lastRun->created_at }}">
                    {{ $lastRun->created_at->diffForHumans() }} · {{ $seconds($duration($lastRun)) }}@if($lastRun->memory) · {{ $lastRun->memory }}@endif
                </small>
            @else
                <b class="cx-tile-v muted"><span class="cx-dot"></span>Never run</b>
                <small>{{ $command->runInManual() ? 'Run it by the buttons above' : 'No runs yet' }}</small>
            @endif
        </a>
        <a class="cx-tile" href="#schedules">
            <span class="cx-k">Next run</span>
            @if($next)
                <b class="cx-tile-v">{{ $next['at']->diffForHumans(['parts' => 2]) }}</b>
                <small title="{{ $next['at']->toDateTimeString() }}">
                    {{ $next['at']->format('H:i:s') }} · {{ $next['schedule']->name ?: $times[$next['schedule']->time_method]->getDescription($next['schedule']) }}
                </small>
            @elseif($schedules->isEmpty())
                <b class="cx-tile-v muted">Not scheduled</b>
                <small>{{ $command->runInSchedule() ? 'Add a schedule below' : 'Schedules are off for it' }}</small>
            @else
                <b class="cx-tile-v muted">Schedules are off</b>
                <small>{{ $schedules->count() }} turned off</small>
            @endif
        </a>
        <a class="cx-tile" href="{{ $runCounts['failed'] ? request()->fullUrlWithQuery(['runs' => 'failed', 'page' => null]) . '#runs' : '#runs' }}">
            <span class="cx-k">Runs</span>
            <b class="cx-tile-v">{{ $runCounts['all'] }}</b>
            <small>
                @if($runCounts['failed'])
                    <span class="bad">{{ $runCounts['failed'] }} failed</span>
                @elseif($runCounts['all'])
                    No failures
                @else
                    None yet
                @endif
                @if($runCounts['running'])
                    · <span class="active">{{ $runCounts['running'] }} running</span>
                @endif
            </small>
        </a>
        <div class="cx-tile">
            <span class="cx-k">Average</span>
            @if($metrics)
                <b class="cx-tile-v">{{ $seconds($metrics->time_avg) }} <i>·</i> {{ $metrics->memory_avg ?? '—' }}</b>
                <small title="Time and memory, min–max">{{ $range($seconds($metrics->time_min), $seconds($metrics->time_max)) }} · {{ $range($metrics->memory_min ?? '—', $metrics->memory_max ?? '—') }}</small>
            @else
                <b class="cx-tile-v muted">—</b>
                <small>Counted by chronos:update-metrics</small>
            @endif
        </div>
    </div>

    @if($attention)
        <div class="cx-attention">
            @foreach($attention as $issue)
                <div class="cx-issue {{ $issue['level'] }}" role="status">
                    @if($issue['level'] === 'info')
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                    @else
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 2.5 20h19L12 3z"/><path d="M12 10v4M12 17h.01"/></svg>
                    @endif
                    <div>
                        <b>{{ $issue['text'] }}</b>
                        <span>{{ $issue['hint'] }}</span>
                        @if(isset($issue['code']))
                            <code class="cx-code">{{ $issue['code'] }}</code>
                        @endif
                    </div>
                    @if(isset($issue['href']))
                        <a class="cx-btn small" href="{{ $issue['href'] }}">{{ $issue['link'] }}</a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="cx-page">
        <div class="cx-main">
            @include('chronos::schedules.table', ['command' => $command])
            @include('chronos::runs.table', ['runs' => $runs])
        </div>
        <aside class="cx-aside">
            @include('chronos::information', ['command' => $command])
        </aside>
    </div>
@endsection

@section('footer_scripts')
    <script>
        var methods = {{ Js::from($times) }};
        var autoScroll = true;

        $('#sigterm').hide();
        $('#sigkill').hide();

        function resetMethodParams() {
            $('#time_params_container').hide();
            $('#time_params_container [id*="time_params_for_"]').hide();
            $('#time_params_container input, #time_params_container select').prop('disabled', true);
        }

        function changeMethod(method) {
            let hasParams = methods[method]['params'].length !== 0;

            resetMethodParams();

            if (!hasParams) {
                return;
            }

            $('#time_params_container').show();
            $(`#time_params_container [id="time_params_for_${method}"]`).show();
            $(`#time_params_container [id="time_params_for_${method}"] input, #time_params_container [id="time_params_for_${method}"] select`).prop('disabled', false);
        }

        // A switch of the form: its state, its box and the minutes of the lock
        function changeSystem(el) {
            const box = el.closest('.cx-switch-box');
            box.classList.toggle('on', el.checked);
            box.querySelector('[data-state]').textContent = el.checked ? 'On' : 'Off';
            if (el.id === 'without_overlapping') {
                $('#without_overlapping_time_wrap').toggle(el.checked);
            }
        }

        resetMethodParams();

        @if($schedule?->time_method)
        changeMethod('{{ $schedule->time_method }}');
        @else
        let firstMethod = Object.getOwnPropertyNames(methods)[0];
        changeMethod(firstMethod);
        @endif

        // The window of a schedule: open at once for ?schedule=, ?new or errors; the name gets the focus;
        // when it is closed, the page is the plain one again without a reload
        const scheduleModal = document.getElementById('scheduleModal');

        if (scheduleModal) {
            scheduleModal.addEventListener('shown.bs.modal', () => scheduleModal.querySelector('#name').focus());
            scheduleModal.addEventListener('hidden.bs.modal', () => {
                const url = new URL(location.href);

                if (url.searchParams.has('schedule') || url.searchParams.has('new')) {
                    url.searchParams.delete('schedule');
                    url.searchParams.delete('new');
                    history.replaceState(null, '', url.toString());
                    document.querySelectorAll('.cx-sched.active, .cx-sched-actions .current').forEach((el) => el.classList.remove('active', 'current'));
                }
            });

            if (scheduleModal.hasAttribute('data-open')) {
                new bootstrap.Modal(scheduleModal).show();
            }
        }

        // "In code" of a schedule opens under its row
        document.querySelectorAll('[data-code-toggle]').forEach((button) => button.addEventListener('click', () => {
            const code = button.closest('.cx-sched').querySelector('.cx-sched-code');
            code.hidden = !code.hidden;
            button.setAttribute('aria-expanded', String(!code.hidden));
        }));

        // A link to a run opens its output
        const openRun = () => {
            const run = location.hash.startsWith('#run-') && document.querySelector(location.hash);
            if (run) {
                run.open = true;
            }
        };
        window.addEventListener('hashchange', openRun);
        openRun();

        var logs = [];

        function runRealTime() {
            $('#runMessageError').hide();
            $('#runCommandInRealTime').prop('disabled', true);

            var xhr = new XMLHttpRequest();

            xhr.open('POST', {{ Js::from(route('chronos.runInRealTime.run', $model)) }}, true);

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        logs = [];
                        document.getElementById('terminal').innerHTML = '';
                        getLogs();
                    }

                    if (xhr.status === 400) {
                        let res = JSON.parse(xhr.responseText);
                        $('#runMessageError').show();
                        $('#runMessageError').text(res.message);
                        $('#runCommandInRealTime').prop('disabled', false);
                    }
                }
            };

            var formData = new FormData();
            var isValidate = true;
            formData.append('_token', '{{ csrf_token() }}');
            document.querySelectorAll('[form=runCommandInRealTime]').forEach((v) => {
                if (v.type !== 'checkbox' || v.checked) {
                    if (v.hasAttribute('required') && !v.value) {
                        isValidate = false;
                        v.reportValidity();
                    }
                    formData.append(v.name, v.value);
                }
            });

            if (isValidate) {
                xhr.send(formData);
            } else {
                $('#runMessageError').hide();
                $('#runCommandInRealTime').prop('disabled', false);
            }
        }

        function getLogs() {
            var timer = setInterval(() => {
                var xhr = new XMLHttpRequest();

                xhr.open('GET', {{ Js::from(route('chronos.runInRealTime.logs', $model)) }} + '?offset=' + logs.length, true);

                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        let data = JSON.parse(xhr.responseText);

                        if (data.signals.sigterm) {
                            $('#sigterm').show();
                        }

                        if (data.signals.sigkill) {
                            $('#sigkill').show();
                        }

                        data.logs.forEach((value, i) => {
                            let index = (data.offset || 0) + i;

                            if (!logs.hasOwnProperty(index)) {
                                logs[index] = value.trim();
                                setLog(value.trim());
                            }
                        });

                        if (data.status) {
                            clearInterval(timer);
                            $('#sigterm').hide();
                            $('#sigkill').hide();
                            $('#runMessageError').hide();
                            $('#runCommandInRealTime').prop('disabled', false);
                        }
                    }
                };

                xhr.send();
            }, 1000);
        }
        function setLog(message) {
            if (message === ':wait:') {
                let entering = document.createElement('span');
                entering.contentEditable = 'true';
                entering.tabIndex = 0;
                entering.className = 'entering';
                entering.onkeyup = sendAnswer;

                document.querySelector('#terminal > div:last-child > pre').append(entering);
                entering.focus();
            } else {
                let row = document.createElement('div');
                let pre = document.createElement('pre');

                pre.textContent = message;
                row.append(pre);
                terminal.append(row);

                if (autoScroll) {
                    row.scrollIntoView({
                        behavior: 'smooth',
                        block: 'end',
                    });
                }
            }
        }
        function sendAnswer(event) {
            if (event.code === 'Enter') {
                event.target.blur();
                event.target.setAttribute('contenteditable', false);
                var xhr = new XMLHttpRequest();

                xhr.open('POST', {{ Js::from(route('chronos.runInRealTime.answer', $model)) }}, true);

                var formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('answer', event.target.textContent);

                xhr.send(formData);
            }
        }
        function sigterm() {
            var xhr = new XMLHttpRequest();

            xhr.open('POST', {{ Js::from(route('chronos.runInRealTime.sigterm', $model)) }}, true);

            var formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');

            xhr.send(formData);
        }
        function sigkill() {
            var xhr = new XMLHttpRequest();

            xhr.open('POST', {{ Js::from(route('chronos.runInRealTime.sigkill', $model)) }}, true);

            var formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');

            xhr.send(formData);
        }

        const terminal = document.getElementById('terminal');

        if (terminal) {
            terminal.addEventListener('scroll', () => {
                autoScroll = terminal.scrollTop + terminal.clientHeight >= terminal.scrollHeight - 10;
            });
        }
    </script>
@endsection
