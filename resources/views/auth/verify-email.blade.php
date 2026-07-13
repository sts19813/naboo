@extends('layouts.auth')

@section('title', 'Verificar correo | ' . config('app.name', 'SuWork'))

@section('content')
    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">Verifica tu correo</h1>
        <div class="text-gray-500 fw-semibold fs-6">Revisa el enlace de verificación que enviamos a tu email</div>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="alert alert-success mb-8">Se envió un nuevo enlace de verificación a tu correo.</div>
    @endif

    <div class="text-gray-600 fs-6 mb-8">
        Antes de continuar, valida tu dirección de correo. Si no recibiste el mensaje, puedes solicitar otro enlace.
    </div>

    <div class="d-flex flex-column gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary w-100">Reenviar correo de verificación</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-light w-100">Cerrar sesión</button>
        </form>
    </div>
@endsection
