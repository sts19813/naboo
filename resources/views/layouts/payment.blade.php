<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pago') | SuWork</title>

    <link href="{{ asset('/metronic/assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('/metronic/assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="{{ asset('/assets/css/app.css') }}">

    @stack('styles')
</head>

<body @include('partials.suwork-flash-attrs') class="bg-light-primary">
    <div class="d-flex flex-column min-vh-100">
        <header class="py-8">
            <div class="container">
                <a href="#" class="d-inline-flex align-items-center text-decoration-none text-dark">
                    <img src="{{ asset('assets/img/Logo.png') }}" alt="SuWork" height="42">
                </a>
            </div>
        </header>

        <main class="flex-grow-1 d-flex align-items-center">
            <div class="container py-8">
                @if (session('error'))
                    <div class="alert alert-danger mb-8">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script src="{{ asset('/metronic/assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('/metronic/assets/js/scripts.bundle.js') }}"></script>
    @include('partials.suwork-toasts')
    @stack('scripts')
</body>

</html>
