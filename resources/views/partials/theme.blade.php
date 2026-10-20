{{--
    The theme before the first paint: the saved choice (or ?theme= when $themeQuery), system by default.
    data-bs-theme is always light or dark, data-theme-choice keeps the choice of the switch.
--}}
<script>
    (function () {
        const key = @json($themeKey ?? 'chronos.theme');
        const themes = ['system', 'light', 'dark'];
        let theme = @json(($themeQuery ?? false) ? request()->query('theme') : null);

        try {
            if (themes.includes(theme)) {
                localStorage.setItem(key, theme);
            } else {
                theme = localStorage.getItem(key);
            }
        } catch (e) {
            //
        }

        const root = document.documentElement;
        const system = window.matchMedia('(prefers-color-scheme: dark)');
        const apply = () => {
            const choice = root.dataset.themeChoice;
            root.dataset.bsTheme = choice === 'system'
                ? (system.matches ? 'dark' : 'light')
                : choice;
        };

        root.dataset.themeChoice = themes.includes(theme) ? theme : 'system';
        apply();
        system.addEventListener('change', apply);
        window.chronosTheme = { key, apply };
    })();
</script>
