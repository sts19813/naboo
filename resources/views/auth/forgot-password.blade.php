@extends('layouts.auth')

@section('title', 'Recuperar contraseña | ' . config('app.name', 'SuWork'))

@section('content')
    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">Recuperar contraseña</h1>
        <div class="text-gray-500 fw-semibold fs-6">Te enviaremos un enlace para restablecer tu acceso</div>
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

    <form method="POST" action="{{ route('password.email') }}" class="form w-100" novalidate>
        @csrf

        <div class="fv-row mb-8">
            <input type="email" placeholder="Correo electrónico" name="email" value="{{ old('email') }}"
                class="form-control form-control-lg bg-transparent @error('email') is-invalid @enderror" required autofocus />
        </div>

        <div class="d-grid mb-8">
            <button type="submit" class="btn btn-primary">Enviar enlace</button>
        </div>

        <div class="text-gray-500 text-center fw-semibold fs-6">
            <a href="{{ route('login') }}" class="link-primary">Volver a iniciar sesión</a>
        </div>
    </form>
@endsection
