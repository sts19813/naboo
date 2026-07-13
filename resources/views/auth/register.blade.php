@extends('layouts.auth')

@section('title', 'Crear cuenta | ' . config('app.name', 'SuWork'))

@section('content')
    <form class="form w-100" method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <div class="text-center mb-11">
            <h1 class="text-gray-900 fw-bolder mb-3">Crear cuenta</h1>
            <div class="text-gray-500 fw-semibold fs-6">Regístrate para usar {{ config('app.name', 'SuWork') }}</div>
        </div>

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
                    Registrarme con Google
                </a>
            </div>

            <div class="separator separator-content my-10">
                <span class="w-125px text-gray-500 fw-semibold fs-7">o con correo</span>
            </div>
        @endif

        <div class="fv-row mb-8">
            <input type="text" placeholder="Nombre completo" name="name" value="{{ old('name') }}" autocomplete="name"
                class="form-control form-control-lg bg-transparent @error('name') is-invalid @enderror" required autofocus />
        </div>

        <div class="fv-row mb-8">
            <input type="email" placeholder="Correo electrónico" name="email" value="{{ old('email') }}" autocomplete="username"
                class="form-control form-control-lg bg-transparent @error('email') is-invalid @enderror" required />
        </div>

        <div class="fv-row mb-8">
            <input type="password" placeholder="Contraseña" name="password" autocomplete="new-password"
                class="form-control form-control-lg bg-transparent @error('password') is-invalid @enderror" required />
        </div>

        <div class="fv-row mb-10">
            <input type="password" placeholder="Confirmar contraseña" name="password_confirmation" autocomplete="new-password"
                class="form-control form-control-lg bg-transparent" required />
        </div>

        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-primary">
                <span class="indicator-label">Crear cuenta</span>
            </button>
        </div>

        <div class="text-gray-500 text-center fw-semibold fs-6">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="link-primary">Inicia sesión</a>
        </div>
    </form>
@endsection
