@extends('layouts.error')

@section('title', '500')

@section('content')
    <div class="error-box">

        <div class="error-image">
            <img src="{{ asset('assets/img/error-500.png') }}" alt="500">
        </div>


        <div class="error-text">
            <h1>Error interno</h1>

            <p>
                Ocurrió un error del servidor.
                Lamentamos el inconveniente, estamos trabajando para resolver el problema.
            </p>

            <a href="{{ url('/') }}">Regresar al inicio</a>
        </div>

    </div>
@endsection