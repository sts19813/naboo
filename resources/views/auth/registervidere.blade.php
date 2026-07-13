@extends('layouts.auth')

@section('title', 'Crear cuenta | Videre')

@section('content')
    <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12 p-lg-20">
        <div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-700px p-lg-15 p-7 shadow-sm">

            <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20">

                <form method="POST" action="{{ route('register') }}" class="form w-100" novalidate>
                    @csrf

                    @if ($errors->any())
                        <div class="alert alert-danger mb-8">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="text-center mb-11">
                        <h1 class="text-gray-900 fw-bolder mb-3">
                            Crear cuenta
                        </h1>
                        <div class="text-gray-500 fw-semibold fs-6">
                            Únete a la red de proveedores Videre
                        </div>
                    </div>

                

                

                    <div class="row g-5 mb-4">
                        <div class="col-md-6 fv-row">
                            <label class="form-label fw-semibold text-gray-700 mb-2">
                                Tipo de proveedor <span class="text-danger">*</span>
                            </label>
                            <select name="provider_type"
                                class="form-select form-select-lg bg-transparent @error('provider_type') is-invalid @enderror"
                                required>
                                <option value="">Selecciona una opción</option>
                                <option value="optometrista" @selected(old('provider_type') === 'optometrista')>
                                    Optometrista
                                </option>
                                <option value="oftalmologo" @selected(old('provider_type') === 'oftalmologo')>
                                    Oftalmólogo
                                </option>
                                <option value="medico" @selected(old('provider_type') === 'medico')>
                                    Médico
                                </option>
                            </select>
                            @error('provider_type')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 fv-row">
                            <label class="form-label fw-semibold text-gray-700 mb-2">
                                Empresa / Consultorio <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="clinic_name" value="{{ old('clinic_name') }}"
                                class="form-control form-control-lg bg-transparent @error('clinic_name') is-invalid @enderror"
                                placeholder="Ej. Clínica Visión Norte" required />
                            @error('clinic_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-5 mb-10">
                        <div class="col-md-6 fv-row">
                            <label class="form-label fw-semibold text-gray-700 mb-2">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}"
                                class="form-control form-control-lg bg-transparent @error('first_name') is-invalid @enderror"
                                placeholder="Tu nombre" required />
                            @error('first_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 fv-row">
                            <label class="form-label fw-semibold text-gray-700 mb-2">
                                Apellido <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}"
                                class="form-control form-control-lg bg-transparent @error('last_name') is-invalid @enderror"
                                placeholder="Tu apellido" required />
                            @error('last_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 fv-row">
                            <label class="form-label fw-semibold text-gray-700 mb-2">
                                Teléfono <span class="text-danger">*</span>
                            </label>
                            <input type="tel" name="phone" value="{{ old('phone') }}"
                                class="form-control form-control-lg bg-transparent @error('phone') is-invalid @enderror"
                                placeholder="Ej. 9991234567" required />
                            @error('phone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 fv-row">
                            <label class="form-label fw-semibold text-gray-700 mb-2">
                                Correo electrónico <span class="text-danger">*</span>
                            </label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                class="form-control form-control-lg bg-transparent @error('email') is-invalid @enderror"
                                placeholder="Ej. correo@dominio.com" required />
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-grid mb-10">
                        <button type="submit" class="btn btn-primary btn-lg">
                            Crear cuenta
                        </button>
                    </div>

                    <div class="text-gray-500 text-center fw-semibold fs-6">
                        ¿Ya tienes cuenta?
                        <a href="{{ route('login') }}" class="link-primary">Inicia sesión</a>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
