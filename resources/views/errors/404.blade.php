@extends('layouts.error')

@section('title', '404')

@section('content')
    <div class="error-box">

        <div class="error-image">
            <img src="{{ asset('assets/img/error-404.png') }}" alt="404">
        </div>


        <div class="error-text">
            <h1>Sitio no encontrado</h1>

            <p>
                Parece que no logramos encontrar el sitio que buscas.
                Lamentamos el inconveniente, estamos trabajando para resolverlo.
            </p>

            <a href="{{ url('/') }}">Regresar al inicio</a>
        </div>

    </div>
@endsection