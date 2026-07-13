@extends('layouts.app')

@section('title', 'Mi perfil | SuWork')

@php
    $name = trim((string) $user->name);
    $nameParts = collect(preg_split('/\s+/', $name ?: '', -1, PREG_SPLIT_NO_EMPTY));
    $initials = $nameParts
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->take(2)
        ->join('');
    $avatarUrl = $user->profilePhotoUrl();
    $roleLabels = $user->roles->pluck('name')
        ->map(fn ($role) => ucfirst(str_replace(['_', '-'], ' ', $role)));
    $profileHomeRoute = ($user->hasRole('inquilino') || $user->hasRole('tenant') || $user->hasRole('tecnico') || $user->hasRole('technician'))
        ? 'maintenance.index'
        : 'dashboard';
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp

@push('styles')
    <style>
        .profile-page {
            padding-block: 30px 18px;
        }

        .profile-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 28px;
        }

        .profile-kicker {
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .profile-title {
            margin: 4px 0 0;
            color: #111827;
            font-size: 28px;
            font-weight: 800;
            line-height: 1.15;
        }

        .profile-subtitle {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
        }

        .profile-summary-card {
            border-radius: 8px;
            overflow: hidden;
        }

        .profile-cover {
            height: 96px;
            background:
                linear-gradient(135deg, rgba(var(--sw-primary-rgb), .92), rgba(31, 34, 52, .94)),
                radial-gradient(circle at 18% 20%, rgba(255, 255, 255, .24), transparent 28%);
        }

        .profile-photo-stack {
            margin-top: -62px;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            margin-inline: auto;
            border: 6px solid #fff;
            border-radius: 20px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 18px 42px rgba(15, 23, 42, .14);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-initials {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--sw-primary);
            color: #fff;
            font-size: 42px;
            font-weight: 800;
        }

        .profile-upload-control {
            position: relative;
        }

        .profile-upload-control input[type="file"] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .profile-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            border: 1px dashed rgba(var(--sw-primary-rgb), .45);
            border-radius: 8px;
            background: var(--sw-primary-light);
            color: var(--sw-primary);
            font-weight: 800;
        }

        .profile-meta-list {
            display: grid;
            gap: 12px;
        }

        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border: 1px solid #eef1f6;
            border-radius: 8px;
            background: #fbfcfe;
        }

        .profile-meta-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: var(--sw-primary);
            box-shadow: inset 0 0 0 1px #edf1f7;
            flex-shrink: 0;
        }

        .profile-card .card-header {
            min-height: 72px;
        }

        .profile-card .card-footer {
            background: #fff;
        }

        .profile-security-note {
            border-radius: 8px;
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
        }

        @media (max-width: 767px) {
            .profile-page {
                padding-block: 18px;
            }

            .profile-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .profile-title {
                font-size: 24px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="profile-page">
        <div class="profile-heading">
            <div>
                <div class="profile-kicker">Cuenta</div>
                <h1 class="profile-title">Mi perfil</h1>
                <div class="profile-subtitle">{{ $user->email }}</div>
            </div>

            <a href="{{ route($profileHomeRoute) }}" class="btn btn-light fw-bold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i>
                Dashboard
            </a>
        </div>

        <div class="row g-8">
            <div class="col-xl-4">
                <div class="card card-flush profile-summary-card mb-8">
                    <div class="profile-cover"></div>
                    <div class="card-body text-center pt-0">
                        <div class="profile-photo-stack">
                            <div class="profile-avatar" data-profile-avatar>
                                @if ($user->profile_photo)
                                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" data-profile-avatar-img>
                                @else
                                    <div class="profile-initials" data-profile-avatar-fallback>{{ $initials ?: 'SW' }}</div>
                                @endif
                            </div>
                        </div>

                        <h2 class="fw-bold text-gray-900 mt-5 mb-1">{{ $user->name }}</h2>
                        <div class="text-muted fw-semibold mb-5">{{ $user->email }}</div>

                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-7">
                            @forelse ($roleLabels as $roleLabel)
                                <span class="badge badge-light-primary fw-bold">{{ $roleLabel }}</span>
                            @empty
                                <span class="badge badge-light-secondary fw-bold">Sin rol asignado</span>
                            @endforelse
                        </div>

                        <form action="{{ route('profile.update.photo') }}" method="POST" enctype="multipart/form-data" data-no-ajax>
                            @csrf

                            <div class="profile-upload-control mb-4">
                                <div class="profile-upload-label">
                                    <i class="ki-outline ki-picture fs-3"></i>
                                    Cambiar foto
                                </div>
                                <input type="file"
                                    name="profile_photo"
                                    accept="image/*"
                                    class="@error('profile_photo') is-invalid @enderror"
                                    required
                                    data-profile-photo-input>
                            </div>

                            @error('profile_photo')
                                <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
                            @enderror

                            <button class="btn btn-primary w-100 fw-bold">
                                <i class="ki-outline ki-check fs-3 me-1"></i>
                                Guardar foto
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card card-flush">
                    <div class="card-header">
                        <div class="card-title">
                            <h3 class="fw-bold mb-0">Resumen</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="profile-meta-list">
                            <div class="profile-meta-item">
                                <span class="profile-meta-icon"><i class="bi bi-person-badge"></i></span>
                                <div class="min-w-0">
                                    <div class="text-muted fs-8 fw-bold text-uppercase">Usuario</div>
                                    <div class="fw-bold text-gray-900 text-truncate">{{ $user->name }}</div>
                                </div>
                            </div>
                            <div class="profile-meta-item">
                                <span class="profile-meta-icon"><i class="bi bi-envelope"></i></span>
                                <div class="min-w-0">
                                    <div class="text-muted fs-8 fw-bold text-uppercase">Correo</div>
                                    <div class="fw-bold text-gray-900 text-truncate">{{ $user->email }}</div>
                                </div>
                            </div>
                            <div class="profile-meta-item">
                                <span class="profile-meta-icon"><i class="bi bi-calendar2-check"></i></span>
                                <div class="min-w-0">
                                    <div class="text-muted fs-8 fw-bold text-uppercase">Alta</div>
                                    <div class="fw-bold text-gray-900">
                                        {{ $user->created_at?->format('d/m/Y') ?: '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <form action="{{ route('profile.update') }}" method="POST" data-no-ajax>
                    @csrf
                    <div class="card card-flush profile-card mb-8">
                        <div class="card-header">
                            <div class="card-title">
                                <div>
                                    <h3 class="fw-bold mb-1">Datos del usuario</h3>
                                    <div class="text-muted fw-semibold fs-7">Información principal de tu cuenta</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-6">
                                <div class="col-md-6">
                                    <label class="required form-label">Nombre completo</label>
                                    <input type="text"
                                        name="name"
                                        value="{{ old('name', $user->name) }}"
                                        class="form-control form-control-solid @error('name') is-invalid @enderror"
                                        placeholder="Nombre y apellidos"
                                        autocomplete="name"
                                        required>
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="required form-label">Correo electrónico</label>
                                    <input type="email"
                                        name="email"
                                        value="{{ old('email', $user->email) }}"
                                        class="form-control form-control-solid @error('email') is-invalid @enderror"
                                        placeholder="correo@empresa.com"
                                        autocomplete="email"
                                        required>
                                    @error('email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            <button class="btn btn-primary fw-bold">
                                <i class="ki-outline ki-check fs-2"></i>
                                Guardar cambios
                            </button>
                        </div>
                    </div>
                </form>

                <form action="{{ route('profile.update.password') }}" method="POST" data-no-ajax>
                    @csrf
                    <div class="card card-flush profile-card">
                        <div class="card-header">
                            <div class="card-title">
                                <div>
                                    <h3 class="fw-bold mb-1">Seguridad</h3>
                                    <div class="text-muted fw-semibold fs-7">Actualización de contraseña</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="profile-security-note d-flex align-items-start gap-3 p-4 mb-6">
                                <i class="ki-outline ki-shield-tick fs-2"></i>
                                <div class="fw-semibold">
                                    Usa una contraseña de al menos 8 caracteres.
                                </div>
                            </div>

                            <div class="row g-6">
                                <div class="col-12">
                                    <label class="required form-label">Contraseña actual</label>
                                    <input type="password"
                                        class="form-control form-control-solid @error('current_password') is-invalid @enderror"
                                        name="current_password"
                                        autocomplete="current-password"
                                        required>
                                    @error('current_password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="required form-label">Nueva contraseña</label>
                                    <input type="password"
                                        class="form-control form-control-solid @error('password') is-invalid @enderror"
                                        name="password"
                                        autocomplete="new-password"
                                        required>
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="required form-label">Confirmar contraseña</label>
                                    <input type="password"
                                        class="form-control form-control-solid"
                                        name="password_confirmation"
                                        autocomplete="new-password"
                                        required>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            <button class="btn btn-primary fw-bold">
                                <i class="ki-outline ki-lock-2 fs-2"></i>
                                Actualizar contraseña
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var input = document.querySelector('[data-profile-photo-input]');
            var avatar = document.querySelector('[data-profile-avatar]');

            if (!input || !avatar) return;

            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file || !file.type.startsWith('image/')) return;

                var reader = new FileReader();
                reader.onload = function (event) {
                    avatar.innerHTML = '<img src="' + event.target.result + '" alt="Vista previa de foto">';
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
@endpush
