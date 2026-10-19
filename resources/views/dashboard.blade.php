<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Chronos dashboard</title>
        <style>
            :root {
                --bg: #0e1116;
                --panel: #161b22;
                --border: #262d36;
                --text: #e6edf3;
                --muted: #8b949e;
                --success: #3fb950;
                --danger: #f85149;
                --warning: #d29922;
                --primary: #58a6ff;
            }

            * { box-sizing: border-box; }

            html, body { height: 100%; }

            body {
                margin: 0;
                background: var(--bg);
                color: var(--text);
                font: 16px/1.4 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
                font-variant-numeric: tabular-nums;
            }

            .page {
                display: flex;
                flex-direction: column;
                gap: 16px;
                /* A screen without scrolling: long lists are cut by their panels. */
                height: 100vh;
                padding: 20px 24px;
            }

            header {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 16px;
            }

            h1 { margin: 0; font-size: 26px; font-weight: 600; }
            h1 small { color: var(--muted); font-weight: 400; }
            h2 { margin: 0 0 12px; font-size: 15px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }

            .clock { font-size: 26px; font-weight: 600; }
            .updated { color: var(--muted); font-size: 14px; margin-left: 12px; }
            .updated.lost { color: var(--danger); font-weight: 600; }

            .banner {
                display: none;
                padding: 14px 18px;
                border-radius: 8px;
                background: rgba(248, 81, 73, .15);
                border: 1px solid var(--danger);
                color: #ffb3ad;
                font-size: 20px;
            }

            .banner b { color: var(--danger); }
            .banner.show { display: block; animation: pulse 2s ease-in-out infinite; }

            @keyframes pulse { 50% { background: rgba(248, 81, 73, .28); } }

            .tiles {
                display: grid;
                grid-template-columns: repeat(7, minmax(0, 1fr));
                gap: 16px;
            }

            .panel {
                background: var(--panel);
                border: 1px solid var(--border);
                border-radius: 8px;
                padding: 16px 18px;
                min-width: 0;
            }

            .tile { text-align: center; }
            .tile .label { color: var(--muted); font-size: 14px; text-transform: uppercase; letter-spacing: .06em; }
            .tile .value { font-size: 48px; font-weight: 700; line-height: 1.15; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .tile .value .unit { font-size: 26px; font-weight: 600; color: var(--muted); margin-left: 4px; }
            .tile .hint { color: var(--muted); font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .tile.bad { border-color: var(--danger); }
            .tile.bad .value { color: var(--danger); }
            .tile.warn .value { color: var(--warning); }
            .tile.good .value { color: var(--success); }
            .tile.active .value { color: var(--primary); }

            .chart { display: flex; align-items: flex-end; gap: 4px; height: 120px; }
            .bar { flex: 1; display: flex; flex-direction: column-reverse; height: 100%; min-width: 0; }
            .bar .s { background: var(--success); opacity: .75; }
            .bar .f { background: var(--danger); }
            .bar .s, .bar .f { border-radius: 2px 2px 0 0; }
            .axis { display: flex; gap: 4px; margin-top: 6px; color: var(--muted); font-size: 12px; }
            .axis span { flex: 1; text-align: center; min-width: 0; }

            .columns {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 16px;
                flex: 1;
                min-height: 0;
            }

            .columns .panel { overflow: hidden; }

            table { width: 100%; border-collapse: collapse; }
            td { padding: 7px 6px; border-top: 1px solid var(--border); vertical-align: top; }
            tr:first-child td { border-top: 0; }
            td.num { text-align: right; white-space: nowrap; color: var(--muted); }
            .name { font-weight: 600; word-break: break-all; }
            .sub { color: var(--muted); font-size: 13px; }
            .empty { color: var(--muted); padding: 8px 0; }

            .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 8px; }
            .dot.success { background: var(--success); }
            .dot.failed, .dot.killed { background: var(--danger); }
            .dot.running { background: var(--primary); animation: blink 1s steps(2) infinite; }
            .dot.missed { background: var(--warning); }
            .dot.upcoming { background: transparent; box-shadow: inset 0 0 0 2px var(--muted); }
            .dot.due { box-shadow: inset 0 0 0 2px var(--primary); animation: blink 1s steps(2) infinite; }

            td.num.soon { color: var(--primary); font-weight: 600; }

            /* A row that has just come to the list, e.g. a run moved from upcoming to running and then to recent. */
            tr.new td { animation: flash 2.5s ease-out; }

            @keyframes flash { from { background: rgba(88, 166, 255, .25); } to { background: transparent; } }

            @keyframes blink { 50% { opacity: .3; } }

            .tag { display: inline-block; white-space: nowrap; font-size: 12px; font-weight: 600; padding: 1px 6px; border-radius: 4px; margin-left: 6px; text-transform: uppercase; }
            .tag.failed, .tag.killed { background: rgba(248, 81, 73, .2); color: var(--danger); }
            .tag.missed { background: rgba(210, 153, 34, .2); color: var(--warning); }
            .tag.schedule, .tag.stuck { background: rgba(248, 81, 73, .2); color: var(--danger); }
            .tag.held { background: rgba(88, 166, 255, .18); color: var(--primary); }

            @media (max-width: 1500px), (max-height: 700px) {
                .page { height: auto; min-height: 100%; }
            }

            @media (max-width: 1500px) {
                .columns { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }

            @media (max-width: 1200px) {
                .tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); }
                .columns { grid-template-columns: minmax(0, 1fr); }
            }

            @media (max-width: 640px) {
                .page { padding: 16px; }
                .tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .tile .value { font-size: 34px; }
                header { flex-direction: column; }
            }
        </style>
    </head>
    <body>
        <div class="page">
            <header>
                <h1>{{ config('app.name') }} <small>/ Chronos</small></h1>
                <div>
                    <span class="clock" id="clock"></span>
                    <span class="updated" id="updated">Loading…</span>
                </div>
            </header>

            <div class="banner" id="banner"></div>

            <section class="tiles">
                <div class="panel tile" id="tile-scheduler">
                    <div class="label">Scheduler</div>
                    <div class="value" id="scheduler">—</div>
                    <div class="hint" id="scheduler-hint">&nbsp;</div>
                </div>
                <div class="panel tile" id="tile-running">
                    <div class="label">Running now</div>
                    <div class="value" id="running">—</div>
                    <div class="hint" id="running-hint">&nbsp;</div>
                </div>
                <div class="panel tile">
                    <div class="label">Runs, {{ \Tkachikov\Chronos\Services\DashboardService::HOURS }}h</div>
                    <div class="value" id="total">—</div>
                    <div class="hint" id="total-hint">&nbsp;</div>
                </div>
                <div class="panel tile" id="tile-failed">
                    <div class="label">Errors, {{ \Tkachikov\Chronos\Services\DashboardService::HOURS }}h</div>
                    <div class="value" id="failed">—</div>
                    <div class="hint" id="failed-hint">&nbsp;</div>
                </div>
                <div class="panel tile">
                    <div class="label">Avg time, {{ \Tkachikov\Chronos\Services\DashboardService::HOURS }}h</div>
                    <div class="value" id="duration">—</div>
                    <div class="hint" id="duration-hint">&nbsp;</div>
                </div>
                <div class="panel tile">
                    <div class="label">Avg memory, {{ \Tkachikov\Chronos\Services\DashboardService::HOURS }}h</div>
                    <div class="value" id="memory">—</div>
                    <div class="hint" id="memory-hint">&nbsp;</div>
                </div>
                <div class="panel tile" id="tile-missed">
                    <div class="label">Missed schedules</div>
                    <div class="value" id="missed">—</div>
                    <div class="hint" id="missed-hint">&nbsp;</div>
                </div>
            </section>

            <section class="panel">
                <h2>Runs by hour</h2>
                <div class="chart" id="chart"></div>
                <div class="axis" id="axis"></div>
            </section>

            <section class="columns">
                <div class="panel">
                    <h2>Upcoming</h2>
                    <table><tbody id="upcoming-list"></tbody></table>
                </div>
                <div class="panel">
                    <h2>Running now</h2>
                    <table><tbody id="running-list"></tbody></table>
                </div>
                <div class="panel">
                    <h2>Problems</h2>
                    <table><tbody id="problems-list"></tbody></table>
                </div>
                <div class="panel">
                    <h2>Recent runs</h2>
                    <table><tbody id="recent-list"></tbody></table>
                </div>
            </section>
        </div>

        <script>
            (function () {
                const dataUrl = @json($dataUrl);
                const refresh = @json($refresh) * 1000;
                // Reload the page from time to time, so that a screen picks up a new version of the dashboard.
                const reloadAfter = 6 * 60 * 60 * 1000;

                let data = null;
                let offset = 0;
                let receivedAt = null;
                let failedRequests = 0;
                // Keys of rows already shown in each list, to highlight the new ones.
                const seen = {};

                const $ = (id) => document.getElementById(id);
                const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
                })[c]);
                const now = () => Date.now() + offset;
                const pad = (n) => String(n).padStart(2, '0');
                const time = (iso) => {
                    const d = new Date(iso);
                    return pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
                };
                const duration = (seconds) => {
                    seconds = Math.max(0, Math.round(seconds));
                    if (seconds < 60) return seconds + 's';
                    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ' + pad(seconds % 60) + 's';
                    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ' + pad(Math.floor(seconds % 3600 / 60)) + 'm';
                    return Math.floor(seconds / 86400) + 'd ' + Math.floor(seconds % 86400 / 3600) + 'h';
                };
                const ago = (iso) => duration((now() - new Date(iso).getTime()) / 1000) + ' ago';
                const elapsed = (iso) => duration((now() - new Date(iso).getTime()) / 1000);
                const tile = (id, cls) => {
                    $(id).classList.remove('bad', 'warn', 'good', 'active');
                    if (cls) $(id).classList.add(cls);
                };
                const rows = (items, empty) => items.length
                    ? items.join('')
                    : '<tr><td class="empty">' + esc(empty) + '</td></tr>';
                const countdown = (iso) => {
                    const seconds = (new Date(iso).getTime() - now()) / 1000;
                    return seconds > 0 ? 'in ' + duration(Math.ceil(seconds)) : 'starting…';
                };
                // Renders a list and highlights rows with keys that were not in it before (not on the first load).
                const list = (id, items, empty) => {
                    const first = !(id in seen);
                    const keys = new Set(items.map((item) => item.key));

                    $(id).innerHTML = rows(items.map((item) => '<tr'
                        + (!first && !seen[id].has(item.key) ? ' class="new"' : '')
                        + '>' + item.html + '</tr>'), empty);
                    seen[id] = keys;
                };

                function renderTiles() {
                    const scheduler = data.scheduler;
                    const totals = data.runs.totals;
                    const stopped = scheduler.status === 'stopped';
                    const errors = totals.failed + totals.killed;
                    const missed = data.schedules.missed.length;

                    $('scheduler').textContent = stopped ? 'Stopped' : 'OK';
                    $('scheduler-hint').textContent = scheduler.last_run_at
                        ? 'beat ' + ago(scheduler.last_run_at) + ' · ' + scheduler.hosts.length + ' host(s)'
                        : 'never ran';
                    tile('tile-scheduler', stopped ? 'bad' : 'good');

                    $('running').textContent = data.runs.running.length;
                    $('running-hint').textContent = data.runs.running.length
                        ? 'longest ' + elapsed(data.runs.running[data.runs.running.length - 1].started_at)
                        : 'nothing is running';
                    tile('tile-running', data.runs.running.length ? 'active' : null);

                    $('total').textContent = totals.total;
                    $('total-hint').textContent = totals.success + ' succeeded';

                    $('failed').textContent = errors;
                    $('failed-hint').textContent = totals.failed + ' failed · ' + totals.killed + ' killed';
                    tile('tile-failed', errors ? 'bad' : 'good');

                    const runTime = data.runs.duration;
                    $('duration').innerHTML = runTime
                        ? (runTime.average < 10 ? esc(runTime.average.toFixed(1)) + '<span class="unit">s</span>' : esc(duration(runTime.average)))
                        : '—';
                    $('duration-hint').textContent = runTime && runTime.max !== null
                        ? 'max ' + duration(runTime.max) + ' · ' + runTime.max_command
                        : 'no finished runs';
                    $('duration-hint').title = $('duration-hint').textContent;

                    const memory = data.runs.memory;
                    // "30.67 MB" → 30.7 and a smaller unit, so that it fits the tile.
                    const [amount, unit] = memory ? memory.average.split(' ') : [];
                    $('memory').innerHTML = memory
                        ? esc(String(Math.round(parseFloat(amount) * 10) / 10)) + '<span class="unit">' + esc(unit) + '</span>'
                        : '—';
                    $('memory-hint').textContent = memory
                        ? 'max ' + memory.max + ' · ' + memory.max_command
                        : 'no finished runs';
                    $('memory-hint').title = $('memory-hint').textContent;

                    $('missed').textContent = missed;
                    $('missed-hint').textContent = 'of ' + data.schedules.enabled + ' enabled';
                    tile('tile-missed', missed ? 'bad' : 'good');

                    const banner = $('banner');
                    banner.classList.toggle('show', stopped);
                    banner.innerHTML = stopped
                        ? '<b>The scheduler is not running.</b> ' + (scheduler.last_run_at
                            ? 'The last <code>schedule:run</code> was ' + esc(ago(scheduler.last_run_at)) + ' (' + esc(time(scheduler.last_run_at)) + ').'
                            : '<code>schedule:run</code> has never run.')
                        : '';
                }

                function renderChart() {
                    const hourly = data.runs.hourly;
                    const max = Math.max(1, ...hourly.map((h) => h.success + h.failed));

                    $('chart').innerHTML = hourly.map((h) => {
                        const title = new Date(h.hour).getHours() + ':00 — ' + h.success + ' succeeded, ' + h.failed + ' failed';
                        return '<div class="bar" title="' + esc(title) + '">'
                            + '<div class="s" style="height:' + (h.success / max * 100) + '%"></div>'
                            + '<div class="f" style="height:' + (h.failed / max * 100) + '%"></div>'
                            + '</div>';
                    }).join('');
                    $('axis').innerHTML = hourly.map((h, i) => '<span>'
                        + (i % 3 === 0 || i === hourly.length - 1 ? pad(new Date(h.hour).getHours()) : '')
                        + '</span>').join('');
                }

                function renderLists() {
                    // A schedule under the lock of withoutOverlapping: its next run will be skipped.
                    const lockTag = (lock) => lock
                        ? '<span class="tag ' + esc(lock.state) + '">' + (lock.state === 'stuck' ? 'lock stuck' : 'locked')
                            + (lock.skipped ? ' · skipped ×' + esc(lock.skipped) : '') + '</span>'
                        : '';

                    list('upcoming-list', data.schedules.upcoming.map((schedule) => ({
                        key: schedule.id + '@' + schedule.next_run_at,
                        html: '<td><span class="dot upcoming" data-due="' + esc(schedule.next_run_at) + '"></span><span class="name">' + esc(schedule.name || schedule.command) + '</span>'
                            + lockTag(schedule.lock)
                            + '<div class="sub">' + (schedule.name ? esc(schedule.command) + ' · ' : '') + 'at ' + esc(time(schedule.next_run_at))
                            + (schedule.lock && schedule.lock.since ? ' · locked since ' + esc(time(schedule.lock.since)) : '') + '</div></td>'
                            + '<td class="num" data-countdown="' + esc(schedule.next_run_at) + '">' + esc(countdown(schedule.next_run_at)) + '</td>',
                    })), 'No enabled schedules');

                    list('running-list', data.runs.running.map((run) => ({
                        key: run.id,
                        html: '<td><span class="dot running"></span><span class="name">' + esc(run.command) + '</span>'
                            + '<div class="sub">since ' + esc(time(run.started_at)) + (run.pid ? ' · pid ' + esc(run.pid) : '') + '</div></td>'
                            + '<td class="num" data-elapsed="' + esc(run.started_at) + '">' + esc(elapsed(run.started_at)) + '</td>',
                    })), 'Nothing is running');

                    const problems = [];
                    data.schedules.stuck.forEach((schedule) => problems.push({
                        key: 'stuck' + schedule.id + '@' + schedule.skipped,
                        html: '<td><span class="dot failed"></span><span class="name">' + esc(schedule.name || schedule.command) + '</span><span class="tag stuck">lock stuck · skipped ×' + esc(schedule.skipped) + '</span>'
                            + '<div class="sub">' + (schedule.name ? esc(schedule.command) + ' · ' : '')
                            + 'nothing is running, but the runs are skipped by withoutOverlapping · <code>schedule:clear-cache</code></div></td>'
                            + '<td class="num">' + (schedule.last_run_at ? 'since ' + esc(time(schedule.last_run_at)) : '') + '</td>',
                    }));
                    data.schedules.missed.forEach((schedule) => problems.push({
                        key: 'missed' + schedule.id + '@' + schedule.missed_since,
                        html: '<td><span class="dot missed"></span><span class="name">' + esc(schedule.name || schedule.command) + '</span><span class="tag missed">missed</span>'
                            + '<div class="sub">due at ' + esc(time(schedule.missed_since)) + '</div></td>'
                            + '<td class="num">' + esc(ago(schedule.missed_since)) + '</td>',
                    }));
                    // One row per command: errors of its runs are counted, failed schedules of the command are joined to it.
                    const failedSchedules = data.schedules.failed.slice();
                    data.runs.errors.forEach((group) => {
                        const schedules = failedSchedules.filter((schedule) => schedule.class && schedule.class === group.class);
                        schedules.forEach((schedule) => failedSchedules.splice(failedSchedules.indexOf(schedule), 1));
                        const last = group.last;

                        problems.push({
                            // Changes with each new error, so that the row is highlighted when the counter grows.
                            key: 'command' + group.class + '@' + last.id,
                            html: '<td><span class="dot ' + esc(last.state) + '"></span><span class="name">' + esc(group.command) + '</span>'
                                + (group.failed ? '<span class="tag failed">failed' + (group.failed > 1 ? ' ×' + esc(group.failed) : '') + '</span>' : '')
                                + (group.killed ? '<span class="tag killed">killed' + (group.killed > 1 ? ' ×' + esc(group.killed) : '') + '</span>' : '')
                                + (schedules.length ? '<span class="tag schedule">schedule failed</span>' : '')
                                + '<div class="sub">'
                                + schedules.map((schedule) => esc(schedule.name || schedule.command) + ' · ').join('')
                                + 'last at ' + esc(time(last.started_at)) + (last.duration !== null ? ' · ' + esc(duration(last.duration)) : '')
                                + '</div></td>'
                                + '<td class="num">' + esc(ago(last.finished_at || last.started_at)) + '</td>',
                        });
                    });
                    // Failed schedules of commands without runs, e.g. without ChronosRunnerTrait.
                    failedSchedules.forEach((schedule) => problems.push({
                        key: 'schedule' + schedule.id + '@' + schedule.last_run_at,
                        html: '<td><span class="dot failed"></span><span class="name">' + esc(schedule.name || schedule.command) + '</span><span class="tag schedule">schedule failed</span>'
                            + '<div class="sub">' + esc(schedule.command) + '</div></td>'
                            + '<td class="num">' + (schedule.last_run_at ? esc(ago(schedule.last_run_at)) : '') + '</td>',
                    }));
                    list('problems-list', problems, 'No problems');

                    list('recent-list', data.runs.recent.map((run) => ({
                        key: run.id,
                        html: '<td><span class="dot ' + esc(run.state) + '"></span><span class="name">' + esc(run.command) + '</span>'
                            + '<div class="sub">' + esc(time(run.started_at)) + (run.memory ? ' · ' + esc(run.memory) : '') + '</div></td>'
                            + '<td class="num">' + (run.duration !== null ? esc(duration(run.duration)) : '') + '</td>',
                    })), 'No runs yet');
                }

                // Every second: the clock, elapsed time of running commands and the age of the data.
                function tick() {
                    const d = new Date(now());
                    $('clock').textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());

                    document.querySelectorAll('[data-elapsed]').forEach((el) => {
                        el.textContent = elapsed(el.dataset.elapsed);
                    });

                    document.querySelectorAll('[data-countdown]').forEach((el) => {
                        const left = new Date(el.dataset.countdown).getTime() - now();
                        el.textContent = countdown(el.dataset.countdown);
                        el.classList.toggle('soon', left <= 10000);
                    });

                    document.querySelectorAll('[data-due]').forEach((el) => {
                        el.classList.toggle('due', new Date(el.dataset.due).getTime() <= now());
                    });

                    if (receivedAt) {
                        const age = Math.round((Date.now() - receivedAt) / 1000);
                        const lost = failedRequests > 0 && age * 1000 > refresh * 2;
                        $('updated').textContent = lost
                            ? 'No connection · data ' + duration(age) + ' old'
                            : 'updated ' + duration(age) + ' ago';
                        $('updated').classList.toggle('lost', lost);
                    }
                }

                async function load() {
                    try {
                        const response = await fetch(dataUrl, {
                            cache: 'no-store',
                            credentials: 'same-origin',
                            headers: { Accept: 'application/json' },
                        });

                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }

                        data = await response.json();
                        // The time of the server, so that elapsed time does not depend on the clock of the screen.
                        offset = new Date(data.generated_at).getTime() - Date.now();
                        receivedAt = Date.now();
                        failedRequests = 0;

                        renderTiles();
                        renderChart();
                        renderLists();
                    } catch (e) {
                        failedRequests++;

                        if (!receivedAt) {
                            $('updated').textContent = 'No connection: ' + e.message;
                            $('updated').classList.add('lost');
                        }
                    } finally {
                        tick();
                        setTimeout(load, refresh);
                    }
                }

                setInterval(tick, 1000);
                setTimeout(() => location.reload(), reloadAfter);
                load();
            })();
        </script>
    </body>
</html>
