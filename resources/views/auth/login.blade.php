@extends('layouts.auth')

@section('title', 'Iniciar sesión | ' . config('app.name', 'SuWork'))

@section('content')
    <form class="form w-100" method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="text-center mb-11">
            <h1 class="text-gray-900 fw-bolder mb-3">Iniciar sesión</h1>
            <div class="text-gray-500 fw-semibold fs-6">Accede a tu cuenta de {{ config('app.name', 'SuWork') }}</div>
        </div>

        @if (session('status'))
            <div class="alert alert-success mb-8">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-8">
                <ul class="mb-0 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (Route::has('auth.google.redirect'))
            <div class="d-grid mb-8">
                <a href="{{ route('auth.google.redirect') }}"
                    class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100">
                    <img alt="Google" src="{{ asset('metronic/assets/media/svg/brand-logos/google-icon.svg') }}" class="h-15px me-3" />
                    Continuar con Google
                </a>
            </div>

            <div class="separator separator-content my-10">
                <span class="w-125px text-gray-500 fw-semibold fs-7">o con correo</span>
            </div>
        @endif

        <div class="fv-row mb-8">
            <input type="email" placeholder="Correo electrónico" name="email" value="{{ old('email') }}" autocomplete="username"
                class="form-control form-control-lg bg-transparent @error('email') is-invalid @enderror" required autofocus />
        </div>

        <div class="fv-row mb-4">
            <input type="password" placeholder="Contraseña" name="password" autocomplete="current-password"
                class="form-control form-control-lg bg-transparent @error('password') is-invalid @enderror" required />
        </div>

        <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
            <label class="form-check form-check-custom form-check-solid">
                <input class="form-check-input" type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }} />
                <span class="form-check-label text-gray-600">Recordarme</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="link-primary">¿Olvidaste tu contraseña?</a>
            @endif
        </div>

        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-primary">
                <span class="indicator-label">Entrar</span>
            </button>
        </div>

        @if (Route::has('register'))
            <div class="text-gray-500 text-center fw-semibold fs-6">
                ¿No tienes cuenta?
                <a href="{{ route('register') }}" class="link-primary">Regístrate</a>
            </div>
        @endif
    </form>
@endsection
