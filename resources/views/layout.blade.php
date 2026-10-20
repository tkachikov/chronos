<!doctype html>
<html lang="en">
    <head>
        <title>{{ isset($title) ? $title . ' · ' : '' }}Chronos</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('chronos::partials.theme')
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
        <script src="https://code.jquery.com/jquery-3.7.0.slim.min.js" integrity="sha256-tG5mcZUtJsZvyKAxYLVXrmjKBVLd6VpVccqz/r4ypFE=" crossorigin="anonymous"></script>
        <style>
            :root, [data-bs-theme="light"] {
                --cx-bg: #f5f7fb;
                --cx-surface: #ffffff;
                --cx-surface-2: #f8fafc;
                --cx-border: #e4e8ef;
                --cx-text: #1f2328;
                --cx-muted: #667085;
                --cx-faint: #98a2b3;
                --cx-primary: #4f46e5;
                --cx-primary-soft: rgba(79, 70, 229, .1);
                --cx-success: #16a34a;
                --cx-success-soft: rgba(22, 163, 74, .12);
                --cx-danger: #dc2626;
                --cx-danger-soft: rgba(220, 38, 38, .1);
                --cx-warning: #d97706;
                --cx-warning-soft: rgba(217, 119, 6, .12);
                --cx-info: #2563eb;
                --cx-terminal: #0e1116;
                --cx-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 1px 3px rgba(16, 24, 40, .06);
                --cx-shadow-hover: 0 4px 12px rgba(16, 24, 40, .08);
            }

            [data-bs-theme="dark"] {
                --cx-bg: #0e1116;
                --cx-surface: #161b22;
                --cx-surface-2: #1b212a;
                --cx-border: #262d36;
                --cx-text: #e6edf3;
                --cx-muted: #8b949e;
                --cx-faint: #6e7681;
                --cx-primary: #8b85ff;
                --cx-primary-soft: rgba(139, 133, 255, .15);
                --cx-success: #3fb950;
                --cx-success-soft: rgba(63, 185, 80, .15);
                --cx-danger: #f85149;
                --cx-danger-soft: rgba(248, 81, 73, .15);
                --cx-warning: #d29922;
                --cx-warning-soft: rgba(210, 153, 34, .15);
                --cx-info: #58a6ff;
                --cx-terminal: #090b0f;
                --cx-shadow: none;
                --cx-shadow-hover: 0 4px 16px rgba(0, 0, 0, .35);
                --bs-body-bg: #0e1116;
                --bs-tertiary-bg: #161b22;
                --bs-border-color: #262d36;
            }

            html { scroll-behavior: smooth; }
            body { background: var(--cx-bg); color: var(--cx-text); }

            .cx-content { max-width: 1680px; margin: 0 auto; padding: 28px 32px 48px; }

            /* Shared pieces of the pages: cards, buttons, tags, messages, modals, pagination */
            .cx-card { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 14px; box-shadow: var(--cx-shadow); min-width: 0; }
            .cx-card-head { display: flex; align-items: center; gap: 10px; padding: 14px 20px; border-bottom: 1px solid var(--cx-border); }
            .cx-card-head h2 { margin: 0; font-size: 15px; font-weight: 600; color: var(--cx-text); }
            .cx-card-head .cx-count { font-size: 12px; color: var(--cx-muted); background: var(--cx-surface-2); border: 1px solid var(--cx-border); border-radius: 999px; padding: 0 8px; }
            .cx-card-head .cx-head-end { margin-left: auto; display: flex; align-items: center; gap: 8px; }
            .cx-card-body { padding: 16px 20px; }

            .cx-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 38px; padding: 0 14px; border-radius: 10px; border: 1px solid var(--cx-border); background: var(--cx-surface); color: var(--cx-text); font-size: 14px; font-weight: 600; text-decoration: none; white-space: nowrap; cursor: pointer; transition: background .12s, border-color .12s, color .12s; }
            .cx-btn:hover { background: var(--cx-surface-2); border-color: var(--cx-faint); color: var(--cx-text); }
            .cx-btn:disabled { opacity: .55; cursor: default; }
            .cx-btn.primary { background: var(--cx-primary); border-color: transparent; color: #fff; }
            .cx-btn.primary:hover { filter: brightness(1.08); color: #fff; }
            .cx-btn.danger { background: var(--cx-danger-soft); border-color: transparent; color: var(--cx-danger); }
            .cx-btn.danger:hover { background: var(--cx-danger); color: #fff; }
            .cx-btn.warning { background: var(--cx-warning-soft); border-color: transparent; color: var(--cx-warning); }
            .cx-btn.warning:hover { background: var(--cx-warning); color: #fff; }
            .cx-btn.ghost { background: transparent; border-color: transparent; color: var(--cx-muted); }
            .cx-btn.ghost:hover { background: var(--cx-surface-2); color: var(--cx-text); }
            .cx-btn.small { height: 32px; padding: 0 10px; font-size: 13px; border-radius: 8px; }
            .cx-btn.icon { width: 32px; padding: 0; }
            .cx-btn svg { flex: none; }

            .cx-tag { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; padding: 1px 7px; border-radius: 6px; color: var(--cx-muted); background: var(--cx-surface-2); border: 1px solid var(--cx-border); white-space: nowrap; }
            .cx-tag.off { color: var(--cx-warning); background: var(--cx-warning-soft); border-color: transparent; }
            .cx-tag.on { color: var(--cx-success); background: var(--cx-success-soft); border-color: transparent; }
            .cx-tag.bad { color: var(--cx-danger); background: var(--cx-danger-soft); border-color: transparent; }
            .cx-tag.info { color: var(--cx-info); background: var(--cx-primary-soft); border-color: transparent; }
            .cx-code { font-size: 12px; color: var(--cx-muted); background: var(--cx-surface-2); border: 1px solid var(--cx-border); border-radius: 6px; padding: 1px 6px; overflow-wrap: anywhere; }
            .cx-none { color: var(--cx-faint); font-size: 13px; }

            /* The state of the last run and the switch of a schedule */
            .cx-dot { flex: none; display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: var(--cx-border); box-shadow: 0 0 0 4px var(--cx-surface-2); }
            .cx-dot.success { background: var(--cx-success); box-shadow: 0 0 0 4px var(--cx-success-soft); }
            .cx-dot.failed, .cx-dot.killed { background: var(--cx-danger); box-shadow: 0 0 0 4px var(--cx-danger-soft); }
            .cx-dot.running { background: var(--cx-info); box-shadow: 0 0 0 4px var(--cx-primary-soft); animation: cx-pulse 1.4s ease-in-out infinite; }
            @keyframes cx-pulse { 50% { opacity: .35; } }
            .cx-switch { flex: none; display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--cx-success); }
            .cx-switch.off { background: transparent; box-shadow: inset 0 0 0 2px var(--cx-faint); }

            .cx-flash { display: flex; align-items: center; gap: 10px; padding: 12px 16px; margin-bottom: 20px; border-radius: 12px; font-size: 14px; font-weight: 500; }
            .cx-flash.success { background: var(--cx-success-soft); color: var(--cx-success); }
            .cx-flash.error { background: var(--cx-danger-soft); color: var(--cx-danger); }
            .cx-flash button { margin-left: auto; border: 0; background: transparent; color: inherit; line-height: 0; opacity: .7; }
            .cx-flash button:hover { opacity: 1; }

            .cx-content .alert { border-radius: 12px; }

            /* The modals follow the theme */
            .modal-content { border-radius: 16px; border: 1px solid var(--cx-border); background: var(--cx-surface); color: var(--cx-text); box-shadow: var(--cx-shadow-hover); }
            .modal-header, .modal-footer { border-color: var(--cx-border); padding: 14px 20px; }
            .modal-header { font-weight: 600; font-size: 15px; }
            .modal-footer { gap: 8px; flex-wrap: wrap; }
            .modal-footer > * { margin: 0; }
            .modal-body { padding: 20px; }
            [data-bs-theme="dark"] .btn-close { filter: invert(1) grayscale(100%); }

            /* The fields of the forms */
            .form-control, .form-select { border-color: var(--cx-border); background-color: var(--cx-surface); color: var(--cx-text); border-radius: 10px; font-size: 14px; min-height: 38px; }
            .form-control:focus, .form-select:focus { border-color: var(--cx-primary); box-shadow: 0 0 0 3px var(--cx-primary-soft); background-color: var(--cx-surface); color: var(--cx-text); }
            .form-switch .form-check-input { width: 36px; height: 20px; margin-top: 0; cursor: pointer; border-color: var(--cx-border); background-color: var(--cx-surface-2); }
            .form-switch .form-check-input:checked { background-color: var(--cx-primary); border-color: var(--cx-primary); }
            .form-switch .form-check-input:focus { box-shadow: 0 0 0 3px var(--cx-primary-soft); }
            .form-check-label { cursor: pointer; }

            /* The pages of the lists */
            .pagination { margin: 0; gap: 4px; flex-wrap: wrap; }
            .pagination .page-link { border-radius: 8px !important; border: 1px solid var(--cx-border); background: var(--cx-surface); color: var(--cx-muted); font-size: 13px; font-weight: 500; padding: 5px 10px; }
            .pagination .page-link:hover { color: var(--cx-text); background: var(--cx-surface-2); }
            .pagination .page-item.active .page-link { background: var(--cx-primary-soft); border-color: transparent; color: var(--cx-primary); font-weight: 600; }
            .pagination .page-item.disabled .page-link { background: transparent; color: var(--cx-faint); }
            .cx-pages { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; font-size: 13px; color: var(--cx-muted); }

            @media (max-width: 768px) {
                .cx-content { padding: 16px 16px 40px; }
                .cx-card { border-radius: 12px; }
                .cx-card-head { padding: 12px 16px; }
                .cx-card-body { padding: 14px 16px; }
                .modal-body { padding: 16px; }
            }
        </style>
        @yield('styles')
    </head>
    <body class="m-0">
        @include('chronos::partials.bar', ['active' => $active ?? 'commands'])
        <main class="cx-content">
            @include('chronos::monitoring.scheduler')
            @foreach(['success' => 'success-message', 'error' => 'error-message'] as $type => $key)
                @if(session($key))
                    <div class="cx-flash {{ $type }}" role="status">
                        @if($type === 'success')
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                        @endif
                        <span>{{ session($key) }}</span>
                        <button type="button" aria-label="Close" onclick="this.parentElement.remove()">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </button>
                    </div>
                @endif
            @endforeach
            @yield('content')
        </main>
        @yield('footer_scripts')
    </body>
</html>
