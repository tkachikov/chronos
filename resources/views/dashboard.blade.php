<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Chronos dashboard</title>
        @include('chronos::partials.theme', ['themeKey' => 'chronos.dashboard.theme', 'themeQuery' => true])
        <style>
            /* Dark by default, light by the theme (partials.theme sets data-bs-theme by the choice or the system). */
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
                --danger-soft: rgba(248, 81, 73, .2);
                --warning-soft: rgba(210, 153, 34, .2);
                --primary-soft: rgba(88, 166, 255, .18);
                --accent: #a371f7;
                --banner: rgba(248, 81, 73, .15);
                --banner-pulse: rgba(248, 81, 73, .28);
                --banner-text: #ffb3ad;
                --flash: rgba(88, 166, 255, .25);
                --shadow: none;
                color-scheme: dark;
            }

            :root[data-bs-theme="light"] {
                --bg: #f6f8fa;
                --panel: #ffffff;
                --border: #d0d7de;
                --text: #1f2328;
                --muted: #59636e;
                --success: #1a7f37;
                --danger: #cf222e;
                --warning: #9a6700;
                --primary: #0969da;
                --danger-soft: rgba(207, 34, 46, .12);
                --warning-soft: rgba(154, 103, 0, .14);
                --primary-soft: rgba(9, 105, 218, .12);
                --accent: #8250df;
                --banner: rgba(207, 34, 46, .08);
                --banner-pulse: rgba(207, 34, 46, .16);
                --banner-text: #82071e;
                --flash: rgba(9, 105, 218, .16);
                --shadow: 0 1px 2px rgba(31, 35, 40, .06);
                color-scheme: light;
            }

            * { box-sizing: border-box; }

            html, body { height: 100%; }

            /* The bar and a screen without scrolling under it */
            body {
                display: flex;
                flex-direction: column;
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
                flex: 1;
                min-height: 0;
                padding: 20px 24px;
            }

            h2 { margin: 0 0 12px; font-size: 15px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }

            .clock { font-size: 20px; font-weight: 600; font-variant-numeric: tabular-nums; }
            .updated { color: var(--muted); font-size: 13px; margin-left: 10px; }
            .updated.lost { color: var(--danger); font-weight: 600; }

            .banner {
                display: none;
                padding: 14px 18px;
                border-radius: 8px;
                background: var(--banner);
                border: 1px solid var(--danger);
                color: var(--banner-text);
                font-size: 20px;
            }

            .banner b { color: var(--danger); }
            .banner.show { display: block; animation: pulse 2s ease-in-out infinite; }

            @keyframes pulse { 50% { background: var(--banner-pulse); } }

            .tiles {
                display: grid;
                grid-template-columns: repeat(7, minmax(0, 1fr));
                gap: 16px;
            }

            .panel {
                background: var(--panel);
                border: 1px solid var(--border);
                box-shadow: var(--shadow);
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

            .load { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 16px; }
            .load-head { display: flex; justify-content: space-between; align-items: baseline; gap: 16px; }
            .load-head h2 { margin-bottom: 4px; }
            .legend { display: flex; gap: 16px; color: var(--muted); font-size: 13px; white-space: nowrap; }
            .legend b { color: var(--text); }
            .legend i { display: inline-block; width: 12px; height: 3px; border-radius: 2px; margin-right: 6px; vertical-align: middle; }
            .legend .running i { background: var(--primary); }
            .legend .memory i { background: repeating-linear-gradient(90deg, var(--accent) 0 5px, transparent 5px 8px); }

            #load-chart { display: block; width: 100%; height: 170px; overflow: visible; }
            #load-chart .grid { stroke: var(--border); stroke-width: 1; }
            #load-chart .tick { fill: var(--muted); font-size: 11px; }
            #load-chart .tick.running { fill: var(--primary); }
            #load-chart .tick.memory { fill: var(--accent); }
            #load-chart .line { fill: none; stroke-width: 2.5; stroke-linejoin: round; stroke-linecap: round; }
            #load-chart .area { stroke: none; opacity: .14; }
            #load-chart .running { stroke: var(--primary); }
            #load-chart .area.running { fill: var(--primary); }
            #load-chart .memory { stroke: var(--accent); }
            /* Dashed: visible when it goes along the line of runs */
            #load-chart .line.memory { stroke-dasharray: 6 5; stroke-width: 2.5; }
            #load-chart .area.memory { fill: var(--accent); }
            #load-chart .point { fill: var(--panel); stroke-width: 2; }
            #load-chart .point.now { animation: blink 2s ease-in-out infinite; }
            #load-chart .hover { fill: transparent; }
            #load-chart .hover:hover { fill: var(--primary-soft); }

            .top th { color: var(--muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; text-align: right; padding: 0 6px 6px; white-space: nowrap; }
            .top th:first-child { text-align: left; }
            .top td { padding: 5px 6px; vertical-align: middle; }
            .top td.cmd { max-width: 0; width: 100%; }
            .top td.cmd .name { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .top .share { height: 3px; margin-top: 3px; border-radius: 2px; background: var(--primary); opacity: .6; }
            .top td.num.bad { color: var(--danger); font-weight: 600; }

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

            @keyframes flash { from { background: var(--flash); } to { background: transparent; } }

            @keyframes blink { 50% { opacity: .3; } }

            .tag { display: inline-block; white-space: nowrap; font-size: 12px; font-weight: 600; padding: 1px 6px; border-radius: 4px; margin-left: 6px; text-transform: uppercase; }
            .tag.failed, .tag.killed, .tag.schedule, .tag.stuck { background: var(--danger-soft); color: var(--danger); }
            .tag.missed { background: var(--warning-soft); color: var(--warning); }
            .tag.held { background: var(--primary-soft); color: var(--primary); }

            /* Narrower screens: the main columns of the top and smaller numbers of the tiles */
            @media (max-width: 1600px) {
                .top .wide { display: none; }
                .tile .value { font-size: 40px; }
                .tile .value .unit { font-size: 22px; }
            }

            @media (max-width: 1500px), (max-height: 700px) {
                html, body { height: auto; }
                .page { flex: none; }
            }

            @media (max-width: 1500px) {
                .columns { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }

            @media (max-width: 1200px) {
                .load { grid-template-columns: minmax(0, 1fr); }
                .tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); }
                .columns { grid-template-columns: minmax(0, 1fr); }
            }

            @media (max-width: 640px) {
                .page { padding: 16px; }
                .tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .tile .value { font-size: 34px; }
            }
        </style>
    </head>
    <body>
        @section('bar_extra')
            <div>
                <span class="clock" id="clock"></span>
                <span class="updated" id="updated">Loading…</span>
            </div>
        @endsection
        @include('chronos::partials.bar', ['active' => 'dashboard'])
        <div class="page">

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

            <section class="load">
                <div class="panel">
                    <div class="load-head">
                        <h2>Load of commands, {{ \Tkachikov\Chronos\Services\DashboardService::HOURS }}h</h2>
                        <div class="legend">
                            <span class="running"><i></i>At once, max <b id="load-running-max">—</b></span>
                            <span class="memory"><i></i>Memory, max <b id="load-memory-max">—</b></span>
                        </div>
                    </div>
                    <svg id="load-chart" role="img" aria-label="Load of commands by hours"></svg>
                </div>
                <div class="panel">
                    <h2>Top {{ \Tkachikov\Chronos\Services\DashboardService::TOP }} commands, {{ \Tkachikov\Chronos\Services\DashboardService::HOURS }}h</h2>
                    <table class="top">
                        <thead>
                            <tr><th>Command</th><th>Runs</th><th>Errors</th><th class="wide">Avg</th><th class="wide">Max mem</th><th>Total</th></tr>
                        </thead>
                        <tbody id="top-list"></tbody>
                    </table>
                </div>
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

                const bytes = (value) => {
                    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                    let unit = 0;
                    while (value >= 1024 && unit < units.length - 1) {
                        value /= 1024;
                        unit++;
                    }
                    return (Math.round(value * 10) / 10) + ' ' + units[unit];
                };

                // A line chart by hours: the peak of runs at once and of their memory, each by its own scale.
                function renderChart() {
                    const svg = $('load-chart');
                    const load = data.runs.load;

                    if (!load || !load.points.length) {
                        svg.innerHTML = '';
                        return;
                    }

                    const points = load.points;
                    const width = svg.clientWidth || 800;
                    const height = svg.clientHeight || 170;
                    const box = { left: 30, right: 58, top: 10, bottom: 22 };
                    const w = width - box.left - box.right;
                    const h = height - box.top - box.bottom;
                    const maxRunning = Math.max(1, load.max_running);
                    const maxMemory = Math.max(1, ...points.map((p) => p.memory_bytes));
                    const step = points.length > 1 ? w / (points.length - 1) : 0;
                    const x = (i) => box.left + i * step;
                    const y = (value, max) => box.top + h - value / max * h;
                    const hour = (p) => pad(new Date(p.hour).getHours());
                    const line = (value, max) => points.map((p, i) => (i ? 'L' : 'M') + x(i).toFixed(1) + ',' + y(value(p), max).toFixed(1)).join(' ');
                    const area = (value, max) => line(value, max)
                        + ' L' + x(points.length - 1).toFixed(1) + ',' + (box.top + h) + ' L' + x(0).toFixed(1) + ',' + (box.top + h) + ' Z';
                    const running = (p) => p.running;
                    const memory = (p) => p.memory_bytes;
                    let out = '';

                    // Grid and scales: runs at once on the left, memory on the right
                    [0, 0.5, 1].forEach((fraction) => {
                        const gy = box.top + h - fraction * h;
                        out += '<line class="grid" x1="' + box.left + '" x2="' + (box.left + w) + '" y1="' + gy + '" y2="' + gy + '"/>'
                            + '<text class="tick running" x="' + (box.left - 8) + '" y="' + (gy + 4) + '" text-anchor="end">'
                            + esc(Math.round(maxRunning * fraction * 10) / 10) + '</text>'
                            + '<text class="tick memory" x="' + (box.left + w + 8) + '" y="' + (gy + 4) + '">'
                            + (fraction ? esc(bytes(maxMemory * fraction)) : '0') + '</text>';
                    });

                    // Hours: each one when there is room, every other one otherwise
                    const every = step >= 34 ? 1 : 2;
                    points.forEach((p, i) => {
                        const last = i === points.length - 1;
                        if (last || i % every === 0) {
                            out += '<text class="tick" x="' + x(i) + '" y="' + (height - 4) + '" text-anchor="middle">'
                                + (last ? 'now' : esc(hour(p))) + '</text>';
                        }
                    });

                    out += '<path class="area memory" d="' + area(memory, maxMemory) + '"/>'
                        + '<path class="area running" d="' + area(running, maxRunning) + '"/>'
                        + '<path class="line running" d="' + line(running, maxRunning) + '"/>'
                        // Over the line of runs: the dashes show both when they go along
                        + '<path class="line memory" d="' + line(memory, maxMemory) + '"/>';

                    points.forEach((p, i) => {
                        const now = i === points.length - 1 ? ' now' : '';
                        out += '<circle class="point running' + now + '" cx="' + x(i) + '" cy="' + y(p.running, maxRunning) + '" r="' + (now ? 4 : 2.5) + '"/>';
                    });

                    // Columns of hours for the tooltip
                    points.forEach((p, i) => {
                        const next = i === points.length - 1 ? 'now' : pad((new Date(p.hour).getHours() + 1) % 24) + ':00';
                        const title = hour(p) + ':00–' + next
                            + ' · up to ' + p.running + ' at once'
                            + (p.memory ? ' · ' + p.memory : '')
                            + ' · ' + p.runs + ' runs'
                            + (p.commands.length ? '\n' + p.commands.join(', ') + (p.more_commands ? ' +' + p.more_commands : '') : '');
                        out += '<rect class="hover" x="' + (x(i) - step / 2) + '" y="' + box.top + '" width="' + Math.max(step, 1) + '" height="' + h + '">'
                            + '<title>' + esc(title) + '</title></rect>';
                    });

                    svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);
                    svg.innerHTML = out;

                    $('load-running-max').textContent = load.max_running;
                    $('load-memory-max').textContent = load.max_memory || '—';
                }

                function renderTop() {
                    const top = data.runs.top || [];
                    const total = (item) => item.total_time ?? item.runs;
                    const max = Math.max(1, ...top.map(total));

                    $('top-list').innerHTML = top.length
                        ? top.map((item) => '<tr>'
                            + '<td class="cmd"><span class="name" title="' + esc(item.command) + '">' + esc(item.command) + '</span>'
                            + '<div class="share" style="width:' + (total(item) / max * 100) + '%"></div></td>'
                            + '<td class="num">' + esc(item.runs) + '</td>'
                            + '<td class="num' + (item.errors ? ' bad' : '') + '">' + esc(item.errors) + '</td>'
                            + '<td class="num wide">' + (item.average_time !== null ? esc(duration(item.average_time)) : '—') + '</td>'
                            + '<td class="num wide">' + esc(item.max_memory || '—') + '</td>'
                            + '<td class="num">' + (item.total_time !== null ? esc(duration(item.total_time)) : '—') + '</td>'
                            + '</tr>').join('')
                        : '<tr><td class="empty" colspan="6">No runs yet</td></tr>';
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
                        renderTop();
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

                window.addEventListener('resize', () => data && renderChart());
                setInterval(tick, 1000);
                setTimeout(() => location.reload(), reloadAfter);
                load();
            })();
        </script>
    </body>
</html>
