<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <base href="{{ asset('/') }}">
    <title>@yield('title', config('app.name', 'SuWork') . ' | Auth')</title>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{ asset('metronic/assets/media/logos/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />

    <link href="{{ asset('metronic/assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('metronic/assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet" type="text/css" />

    <style>
        .auth-brand-strip {
            background-image: linear-gradient(230deg, var(--sw-primary) 0%, var(--sw-primary-hover) 45%, var(--sw-primary-hover) 100%);
        }

        .btn-primary,
        .btn.btn-primary {
            background-color: var(--sw-primary) !important;
            border-color: var(--sw-primary-border) !important;
            color: #fff !important;
        }
    </style>

    @stack('styles')
</head>
<body id="kt_body" @include('partials.suwork-flash-attrs') class="app-blank">
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

    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1 bg-body">
                <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                    <div class="w-lg-500px p-10 p-lg-15">
                        @yield('content')
                    </div>
                </div>

                <div class="d-flex flex-center px-10 mx-auto w-100">
                    <div class="text-gray-500 fs-7 fw-semibold">&copy; {{ now()->year }} {{ config('app.name', 'SuWork') }}</div>
                </div>
            </div>

            <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2"
                style="background-image: url('{{ asset('metronic/assets/media/misc/auth-bg.png') }}');">
                <div class="d-flex flex-column flex-center py-10 py-lg-15 px-5 px-md-15 w-100 auth-brand-strip bg-opacity-75">
                    <a href="{{ url('/') }}" class="mb-8 mb-lg-12">
                        <img alt="Logo SuHomes" src="{{ asset('assets/img/suhomes-app-logo.svg') }}" class="h-60px h-lg-75px" />
                    </a>

                    <img class="d-none d-lg-block mx-auto w-275px w-md-50 w-xl-500px mb-10 mb-lg-20"
                        src="{{ asset('metronic/assets/media/misc/auth-screens.png') }}" alt="SuHomes" />

                    <h1 class="d-none d-lg-block text-white fs-2qx fw-bolder text-center mb-7">
                        Gestiona tus propiedades con control total
                    </h1>

                    <div class="d-none d-lg-block text-white fs-base text-center opacity-90">
                        Expedientes, inquilinos, cobranza e inventarios en una sola plataforma.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>var hostUrl = "{{ asset('metronic/assets') }}/";</script>
    <script src="{{ asset('metronic/assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('metronic/assets/js/scripts.bundle.js') }}"></script>
    @include('partials.suwork-toasts')

    @stack('scripts')
</body>
</html>
