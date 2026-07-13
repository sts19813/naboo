<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">


<head>
    <meta charset="utf-8">
    <title>@yield('title', 'SuWork')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Vendor Stylesheets (para páginas específicas, opcional) -->
    <link href="{{ asset('/metronic/assets/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ asset('/metronic/assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet"
        type="text/css" />
    <!-- Global Stylesheets Bundle (obligatorios) -->
    <link href="{{ asset('/metronic/assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('/metronic/assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="{{ asset('/assets/css/app.css') }}">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" />
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>

    <style>
        @media (min-width: 1800px) {
            .container, .container-lg, .container-md, .container-sm, .container-xl, .container-xxl {
                max-width: 1700px;
            }
        }
    </style>

    @stack('styles')
</head>

@php
    $isSidebarMinimized = request()->cookie('sidebar_minimize_state', 'on') === 'on';
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp

<body id="kt_app_body"
    data-kt-app-sidebar-enabled="true"
    data-kt-app-sidebar-fixed="true"
    data-kt-app-sidebar-hoverable="false"
    @if ($isSidebarMinimized)
        data-kt-app-sidebar-minimize="on"
    @endif
    @include('partials.suwork-flash-attrs')
    class="app-default su-admin-layout">
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = "light";
        var themeMode;

        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }

            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }

            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>

    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            <div class="app-wrapper" id="kt_app_wrapper">
                @include('partials.sidebar')

                <main class="app-main" id="kt_app_main">
                    <div class="app-shell">
                        <div class="su-layout-content">
                            @if (session('status'))
                                <div class="alert alert-success d-flex align-items-center mb-6">
                                    <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                                    <div class="fw-semibold">{{ session('status') }}</div>
                                </div>
                            @endif

                            @if ($viewErrors->any())
                                <div class="alert alert-danger mb-6">
                                    <div class="fw-bold mb-1">Revisa la información capturada.</div>
                                    <ul class="mb-0 ps-4">
                                        @foreach ($viewErrors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @yield('content')
                        </div>

                        @include('partials.footer')
                    </div>
                </main>
            </div>
        </div>
    </div>

    <script>
        var hostUrl = "{{ asset('assets') }}/";
    </script>


    <script src="{{ asset('/metronic/assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('/metronic/assets/js/scripts.bundle.js') }}"></script>

    <script src="{{ asset('/metronic/assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>

    <script>
        function setThemeMode(mode) {
            localStorage.setItem('data-bs-theme', mode);

            if (mode === 'system') {
                mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            document.documentElement.setAttribute('data-bs-theme', mode);
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-sidebar-theme-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var currentMode = document.documentElement.getAttribute('data-bs-theme') || 'light';
                    setThemeMode(currentMode === 'dark' ? 'light' : 'dark');
                });
            });

            var sidebarToggle = document.getElementById('kt_app_sidebar_toggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    setTimeout(function () {
                        var isMinimized = document.body.getAttribute('data-kt-app-sidebar-minimize') === 'on';
                        document.cookie = 'sidebar_minimize_state=' + (isMinimized ? 'on' : 'off') + '; path=/; max-age=31536000; SameSite=Lax';
                    }, 80);
                });
            }
        });
    </script>

    @include('partials.suwork-toasts')

    @stack('scripts')
</body>

</html>
