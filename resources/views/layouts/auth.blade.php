<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <base href="{{ asset('/') }}">
    <title>@yield('title', config('app.name', 'Naboo') . ' | Acceso')</title>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/naboo-mark.svg') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" />

    <link href="{{ asset('metronic/assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('metronic/assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet" type="text/css" />

    @stack('styles')
</head>
<body id="kt_body" @include('partials.suwork-flash-attrs') class="app-blank naboo-auth">
    <script>
        var defaultThemeMode = "light";
        var themeMode;

        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else if (localStorage.getItem("data-bs-theme") !== null) {
                themeMode = localStorage.getItem("data-bs-theme");
            } else {
                themeMode = defaultThemeMode;
            }

            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }

            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>

    <div class="d-flex flex-column flex-root min-vh-100" id="kt_app_root">
        <div class="naboo-auth-shell">
            <aside class="naboo-auth-brand">
                <a href="{{ url('/') }}" class="naboo-auth-logo" aria-label="Ir al inicio de Naboo">
                    <img alt="Naboo" src="{{ asset('assets/img/naboo-logo-white.svg') }}" />
                </a>

                <div class="naboo-auth-brand-copy">
                    <span class="naboo-auth-kicker">Administración inmobiliaria</span>
                    <h2>Todo tu portafolio.<br>Un solo lugar.</h2>
                    <p>Propiedades, expedientes, cobranza, inventarios y mantenimiento con una experiencia clara y ordenada.</p>
                </div>

                <div class="naboo-auth-brand-footer">
                    <span class="naboo-auth-brand-dot"></span>
                    <span>Control simple. Decisiones claras.</span>
                </div>
            </aside>

            <main class="naboo-auth-main">
                <div class="naboo-auth-mobile-brand">
                    <a href="{{ url('/') }}" aria-label="Ir al inicio de Naboo">
                        <img alt="Naboo" src="{{ asset('assets/img/naboo-logo.svg') }}" />
                    </a>
                </div>

                <div class="naboo-auth-form-wrap">
                    <div class="naboo-auth-card">
                        @yield('content')
                    </div>
                </div>

                <div class="naboo-auth-copyright">
                    &copy; {{ now()->year }} {{ config('app.name', 'Naboo') }}
                </div>
            </main>
        </div>
    </div>

    <script>var hostUrl = "{{ asset('metronic/assets') }}/";</script>
    <script src="{{ asset('metronic/assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('metronic/assets/js/scripts.bundle.js') }}"></script>
    @include('partials.suwork-toasts')

    @stack('scripts')
</body>
</html>
