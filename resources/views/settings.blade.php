{{-- resources/views/settings.blade.php --}}
@extends('layouts.app')

@section('title', 'Configuracion')

@section('content')
<div class="container mt-5">
    <h1>Configuracion</h1>

    @if (session('status'))
        <div class="alert alert-success mt-3" role="alert">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mt-3" role="alert">
            {{ session('error') }}
        </div>
    @endif
    @role('admin')
    <a href="{{ route('google.auth') }}" class="btn btn-warning me-2">Enlazar cuenta de google</a>
    @endrole
</div>
@endsection