@extends('layouts.app')

@section('title', 'Editar Propietario | SuWork')

@section('content')
    <div class="py-10 property-module">
        <div class="mb-8">
            <a href="{{ route('owners.index') }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver a propietarios
            </a>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">Editar propietario</h3>
            </div>
            <div class="card-body pt-0">
                <form method="POST" action="{{ route('owners.update', $owner) }}">
                    @csrf
                    @method('PUT')

                    @include('owners.partials.form-fields', ['owner' => $owner])

                    <div class="d-flex justify-content-end gap-3 mt-8">
                        <a href="{{ route('owners.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

