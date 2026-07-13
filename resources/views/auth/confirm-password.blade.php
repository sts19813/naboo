@extends('layouts.auth')

@section('title', 'Confirmar contraseña | ' . config('app.name', 'SuWork'))

@section('content')
    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">Confirmar contraseña</h1>
        <div class="text-gray-500 fw-semibold fs-6">Por seguridad, confirma tu contraseña para continuar</div>
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

    <form method="POST" action="{{ route('password.confirm') }}" class="form w-100" novalidate>
        @csrf

        <div class="fv-row mb-10">
            <input type="password" placeholder="Contraseña" name="password" autocomplete="current-password"
                class="form-control form-control-lg bg-transparent @error('password') is-invalid @enderror" required autofocus />
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Confirmar</button>
        </div>
    </form>
@endsection
