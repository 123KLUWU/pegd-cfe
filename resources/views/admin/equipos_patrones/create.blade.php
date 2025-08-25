{{-- resources/views/admin/equipos_patrones/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Registrar Equipo Patrón')

@section('content')
<div class="container mt-5">
    <h1>Registrar Nuevo Equipo Patrón</h1>

    @if (session('error'))
        <div class="alert alert-danger mt-3" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('admin.equipos-patrones.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="identificador" class="form-label">Identificador (TAG):</label>
            <input type="text" class="form-control @error('identificador') is-invalid @enderror" id="identificador" name="identificador" value="{{ old('identificador') }}" required autofocus>
            @error('identificador')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción:</label>
            <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion" rows="3">{{ old('descripcion') }}</textarea>
            @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="marca" class="form-label">Marca:</label>
            <input type="text" class="form-control @error('marca') is-invalid @enderror" id="marca" name="marca" value="{{ old('marca') }}">
            @error('marca')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="modelo" class="form-label">Modelo:</label>
            <input type="text" class="form-control @error('modelo') is-invalid @enderror" id="modelo" name="modelo" value="{{ old('modelo') }}">
            @error('modelo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="numero_serie" class="form-label">Número de Serie:</label>
            <input type="text" class="form-control @error('numero_serie') is-invalid @enderror" id="numero_serie" name="numero_serie" value="{{ old('numero_serie') }}">
            @error('numero_serie')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="ultima_calibracion" class="form-label">Fecha Última Calibración:</label>
            <input type="date" class="form-control @error('ultima_calibracion') is-invalid @enderror" id="ultima_calibracion" name="ultima_calibracion" value="{{ old('ultima_calibracion') }}">
            @error('ultima_calibracion')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary">Registrar Equipo</button>
        <a href="{{ route('admin.equipos-patrones.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
