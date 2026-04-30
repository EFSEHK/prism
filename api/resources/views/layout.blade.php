<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', config('app.name', 'LASK'))</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        <script>
            (function () {
                var storedTheme = localStorage.getItem('lask-theme');
                var preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                var theme = storedTheme || preferredTheme;

                document.documentElement.setAttribute('data-theme', theme);
            })();
        </script>
        <style>
            :root {
                color-scheme: light;
                --bg: #f4f7fb;
                --panel: rgba(255, 255, 255, 0.92);
                --panel-border: rgba(15, 23, 42, 0.08);
                --text: #0f172a;
                --muted: #64748b;
                --primary: #2563eb;
                --primary-soft: rgba(37, 99, 235, 0.1);
                --soft-surface: rgba(248, 250, 252, 0.9);
                --soft-pill: rgba(255, 255, 255, 0.78);
                --soft-badge: rgba(15, 23, 42, 0.04);
                --input-bg: #ffffff;
                --input-border: rgba(148, 163, 184, 0.32);
                --error-bg: rgba(239, 68, 68, 0.08);
                --error-border: rgba(239, 68, 68, 0.2);
                --error-text: #b91c1c;
                --success-bg: rgba(16, 185, 129, 0.1);
                --success-border: rgba(16, 185, 129, 0.24);
                --success-text: #047857;
                --info-bg: rgba(37, 99, 235, 0.08);
                --info-border: rgba(37, 99, 235, 0.16);
                --info-text: #1d4ed8;
                --shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
            }

            html[data-theme='dark'] {
                color-scheme: dark;
                --bg: #07111f;
                --panel: rgba(15, 23, 42, 0.86);
                --panel-border: rgba(148, 163, 184, 0.16);
                --text: #e2e8f0;
                --muted: #94a3b8;
                --primary: #60a5fa;
                --primary-soft: rgba(96, 165, 250, 0.16);
                --soft-surface: rgba(15, 23, 42, 0.72);
                --soft-pill: rgba(15, 23, 42, 0.74);
                --soft-badge: rgba(148, 163, 184, 0.12);
                --input-bg: rgba(15, 23, 42, 0.88);
                --input-border: rgba(148, 163, 184, 0.22);
                --error-bg: rgba(127, 29, 29, 0.32);
                --error-border: rgba(248, 113, 113, 0.22);
                --error-text: #fecaca;
                --success-bg: rgba(6, 78, 59, 0.34);
                --success-border: rgba(52, 211, 153, 0.24);
                --success-text: #a7f3d0;
                --info-bg: rgba(30, 58, 138, 0.3);
                --info-border: rgba(96, 165, 250, 0.24);
                --info-text: #bfdbfe;
                --shadow: 0 24px 60px rgba(2, 6, 23, 0.42);
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                font-family: 'Instrument Sans', sans-serif;
                color: var(--text);
                background: var(--bg);
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            code {
                font-family: Consolas, Monaco, monospace;
                font-size: 0.88rem;
            }

            .button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 12px 18px;
                border-radius: 14px;
                border: 1px solid transparent;
                background: var(--soft-pill);
                color: var(--text);
                font-weight: 600;
                font: inherit;
                cursor: pointer;
                transition: 0.2s ease;
            }

            .button:hover {
                transform: translateY(-1px);
            }

            .button-primary {
                background: var(--primary);
                color: #fff;
                box-shadow: 0 16px 30px rgba(37, 99, 235, 0.24);
                border-color: transparent;
            }

            .button-secondary {
                background: var(--soft-pill);
                color: var(--text);
                border-color: var(--panel-border);
            }

            .theme-toggle {
                min-width: 148px;
            }
        </style>
        @yield('styles')
    </head>
    <body>
        @yield('content')

        <script>
            (function () {
                var toggles = document.querySelectorAll('[data-theme-toggle]');

                if (! toggles.length) {
                    return;
                }

                var root = document.documentElement;

                function applyTheme(theme) {
                    root.setAttribute('data-theme', theme);
                    localStorage.setItem('lask-theme', theme);

                    toggles.forEach(function (toggle) {
                        var label = toggle.querySelector('[data-theme-toggle-label]');

                        if (label) {
                            label.textContent = theme === 'dark' ? 'Switch to light' : 'Switch to dark';
                        }

                        toggle.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
                    });
                }

                applyTheme(root.getAttribute('data-theme') || 'light');

                toggles.forEach(function (toggle) {
                    toggle.addEventListener('click', function () {
                        var nextTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                        applyTheme(nextTheme);
                    });
                });
            })();
        </script>
        @yield('scripts')
    </body>
</html>
