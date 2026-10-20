{{--
    The bar of the panel and the dashboard. Its own colors by data-bs-theme, so that it is the same on both pages.
    $active: commands|dashboard; the page can add its items by the section bar_extra.
    The links of the panel are hidden on the dashboard opened by a token: the panel is behind other middlewares.
--}}
@php
    $active ??= null;
    $byToken = $active === 'dashboard' && request()->query('token');
@endphp
<style>
    .cx-bar {
        --bar-bg: rgba(255, 255, 255, .85);
        --bar-border: #e4e8ef;
        --bar-text: #1f2328;
        --bar-muted: #667085;
        --bar-hover: #f1f4f9;
        --bar-brand: #4f46e5;
        --bar-brand-soft: rgba(79, 70, 229, .1);
        position: sticky;
        top: 0;
        z-index: 1020;
        display: flex;
        align-items: center;
        gap: 24px;
        min-height: 57px;
        padding: 10px 32px;
        background: var(--bar-bg);
        backdrop-filter: blur(8px);
        border-bottom: 1px solid var(--bar-border);
        font: 500 15px/1.4 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        color: var(--bar-text);
    }

    [data-bs-theme="dark"] .cx-bar {
        --bar-bg: rgba(22, 27, 34, .85);
        --bar-border: #262d36;
        --bar-text: #e6edf3;
        --bar-muted: #8b949e;
        --bar-hover: #1b212a;
        --bar-brand: #8b85ff;
        --bar-brand-soft: rgba(139, 133, 255, .15);
    }

    .cx-bar * { box-sizing: border-box; }
    .cx-brand { display: flex; align-items: center; gap: 10px; color: var(--bar-text); text-decoration: none; font-weight: 700; font-size: 18px; }
    .cx-brand:hover { color: var(--bar-text); }
    .cx-logo { display: grid; place-items: center; width: 32px; height: 32px; border-radius: 9px; background: var(--bar-brand); color: #fff; }
    .cx-app { color: var(--bar-muted); font-weight: 500; font-size: 14px; padding: 2px 10px; border: 1px solid var(--bar-border); border-radius: 999px; }

    .cx-nav { display: flex; gap: 4px; }
    .cx-nav a { color: var(--bar-muted); text-decoration: none; font-weight: 500; padding: 6px 12px; border-radius: 8px; }
    .cx-nav a:hover { color: var(--bar-text); background: var(--bar-hover); }
    .cx-nav a.active { color: var(--bar-brand); background: var(--bar-brand-soft); }

    .cx-bar-end { margin-left: auto; display: flex; align-items: center; gap: 16px; }
    .cx-themes { display: inline-flex; border: 1px solid var(--bar-border); border-radius: 8px; overflow: hidden; }
    .cx-themes button { background: transparent; border: 0; color: var(--bar-muted); padding: 6px 9px; line-height: 0; cursor: pointer; }
    .cx-themes button + button { border-left: 1px solid var(--bar-border); }
    .cx-themes button:hover { color: var(--bar-text); }
    .cx-themes button[aria-pressed="true"] { background: var(--bar-brand-soft); color: var(--bar-brand); }

    @media (max-width: 768px) {
        /* The brand and the switch of the theme on the first line, the links on the second */
        .cx-bar { padding: 8px 16px 0; gap: 0 12px; flex-wrap: wrap; }
        .cx-app { display: none; }
        .cx-brand { font-size: 17px; }
        .cx-bar-end { gap: 10px; }
        .cx-nav { order: 3; flex-basis: 100%; gap: 0; margin: 4px -16px 0; padding: 0 8px; overflow-x: auto; scrollbar-width: none; }
        .cx-nav::-webkit-scrollbar { display: none; }
        .cx-nav a { flex: none; padding: 8px 10px 10px; border-radius: 0; border-bottom: 2px solid transparent; font-size: 14px; }
        .cx-nav a:hover { background: transparent; }
        .cx-nav a.active { background: transparent; border-bottom-color: var(--bar-brand); }
    }
</style>
<header class="cx-bar">
    @if($byToken)
        <span class="cx-brand">
    @else
        <a class="cx-brand" href="{{ route('chronos.main') }}">
    @endif
        <span class="cx-logo">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        </span>
        Chronos
        <span class="cx-app">{{ config('app.name') }}</span>
    @if($byToken)
        </span>
    @else
        </a>
    @endif
    @unless($byToken)
        <nav class="cx-nav">
            <a href="{{ route('chronos.main') }}" @class(['active' => $active === 'commands'])>Commands</a>
            <a href="{{ route('chronos.dashboard') }}" @class(['active' => $active === 'dashboard'])>Dashboard</a>
        </nav>
    @endunless
    <div class="cx-bar-end">
        @yield('bar_extra')
        <div class="cx-themes" role="group" aria-label="Theme">
            <button type="button" data-theme-option="system" title="System" aria-label="System theme">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            </button>
            <button type="button" data-theme-option="light" title="Light" aria-label="Light theme">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
            </button>
            <button type="button" data-theme-option="dark" title="Dark" aria-label="Dark theme">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </button>
        </div>
    </div>
</header>
<script>
    // The switch of the theme, the choice is saved in the browser (see partials.theme).
    (function () {
        const root = document.documentElement;
        const buttons = document.querySelectorAll('[data-theme-option]');
        const render = () => buttons.forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.themeOption === root.dataset.themeChoice));
        });

        buttons.forEach((button) => button.addEventListener('click', () => {
            root.dataset.themeChoice = button.dataset.themeOption;

            try {
                localStorage.setItem(window.chronosTheme.key, button.dataset.themeOption);
            } catch (e) {
                //
            }

            window.chronosTheme.apply();
            render();
        }));

        render();
    })();
</script>
