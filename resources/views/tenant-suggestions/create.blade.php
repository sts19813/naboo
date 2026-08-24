@extends('layouts.app')

@section('title', 'Sugerencias | Naboo')

@section('content')
    <div class="container-xxl py-8">
        <div class="card shadow-sm">
            <div class="card-body p-6 p-lg-10">
                <div class="d-flex align-items-start gap-4 mb-8">
                    <div class="symbol symbol-50px">
                        <span class="symbol-label bg-light-primary text-primary">
                            <i class="bi bi-chat-square-text fs-2x"></i>
                        </span>
                    </div>
                    <div>
                        <h1 class="fs-2x fw-bold mb-2">Sugerencias</h1>
                        <p class="text-gray-600 fs-5 mb-0">
                            Comparte comentarios o ideas para mejorar tu experiencia. El administrador las recibirá en el buzón.
                        </p>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                        <i class="bi bi-check-circle-fill fs-2 me-3"></i>
                        <div class="fw-semibold">{{ session('success') }}</div>
                    </div>
                @endif

                <form method="POST" action="{{ route('tenant-suggestions.store') }}" class="mw-800px">
                    @csrf

                    <div class="mb-6">
                        <label for="title" class="required form-label fw-semibold fs-6">Título</label>
                        <input id="title" name="title" type="text"
                            class="form-control form-control-solid @error('title') is-invalid @enderror"
                            value="{{ old('title') }}" maxlength="190" required autofocus
                            placeholder="Resume tu sugerencia">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-8">
                        <label for="message" class="required form-label fw-semibold fs-6">Mensaje</label>
                        <textarea id="message" name="message" rows="7" maxlength="5000" required
                            class="form-control form-control-solid @error('message') is-invalid @enderror"
                            placeholder="Cuéntanos tu comentario, feedback o sugerencia...">{{ old('message') }}</textarea>
                        <div class="form-text">Máximo 5,000 caracteres.</div>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-2"></i> Enviar sugerencia
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
