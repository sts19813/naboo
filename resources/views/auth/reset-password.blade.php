@extends('layouts.auth')

@section('title', 'Restablecer contraseña | ' . config('app.name', 'SuWork'))

@section('content')
    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">Restablecer contraseña</h1>
        <div class="text-gray-500 fw-semibold fs-6">Define una nueva contraseña para tu cuenta</div>
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

    <form method="POST" action="{{ route('password.store') }}" class="form w-100" novalidate>
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="fv-row mb-8">
            <input type="email" placeholder="Correo electrónico" name="email" value="{{ old('email', $request->email) }}"
                class="form-control form-control-lg bg-transparent @error('email') is-invalid @enderror" required autofocus />
        </div>

        <div class="fv-row mb-8">
            <input type="password" placeholder="Nueva contraseña" name="password" autocomplete="new-password"
                class="form-control form-control-lg bg-transparent @error('password') is-invalid @enderror" required />
        </div>

        <div class="fv-row mb-10">
            <input type="password" placeholder="Confirmar contraseña" name="password_confirmation" autocomplete="new-password"
                class="form-control form-control-lg bg-transparent" required />
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
        </div>
    </form>
@endsection
