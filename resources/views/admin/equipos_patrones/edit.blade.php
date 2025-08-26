{{-- resources/views/admin/equipos_patrones/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Editar Equipo Patrón: ' . $equipoPatron->identificador)

@section('content')
<div class="container mt-5">
    <h1>Editar Equipo Patrón: {{ $equipoPatron->identificador }}</h1>

    @if (session('error'))
        <div class="alert alert-danger mt-3" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('admin.equipos-patrones.update', $equipoPatron->id) }}" method="POST">
        @csrf
        @method('PUT') {{-- Usar el método PUT para actualizaciones --}}

        <div class="mb-3">
            <label for="identificador" class="form-label">Identificador (TAG):</label>
            <input type="text" class="form-control @error('identificador') is-invalid @enderror" id="identificador" name="identificador" value="{{ old('identificador', $equipoPatron->identificador) }}" required autofocus>
            @error('identificador')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción:</label>
            <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion" rows="3">{{ old('descripcion', $equipoPatron->descripcion) }}</textarea>
            @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="marca" class="form-label">Marca:</label>
            <input type="text" class="form-control @error('marca') is-invalid @enderror" id="marca" name="marca" value="{{ old('marca', $equipoPatron->marca) }}">
            @error('marca')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="modelo" class="form-label">Modelo:</label>
            <input type="text" class="form-control @error('modelo') is-invalid @enderror" id="modelo" name="modelo" value="{{ old('modelo', $equipoPatron->modelo) }}">
            @error('modelo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="numero_serie" class="form-label">Número de Serie:</label>
            <input type="text" class="form-control @error('numero_serie') is-invalid @enderror" id="numero_serie" name="numero_serie" value="{{ old('numero_serie', $equipoPatron->numero_serie) }}">
            @error('numero_serie')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="ultima_calibracion" class="form-label">Fecha Última Calibración:</label>
            <input type="date" class="form-control @error('ultima_calibracion') is-invalid @enderror" id="ultima_calibracion" name="ultima_calibracion" value="{{ old('ultima_calibracion', $equipoPatron->ultima_calibracion ? $equipoPatron->ultima_calibracion->format('Y-m-d') : '') }}">
            @error('ultima_calibracion')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <p class="form-label">Próxima Calibración: <strong>{{ $equipoPatron->proxima_calibracion ? $equipoPatron->proxima_calibracion->format('d/m/Y') : 'N/A' }}</strong></p>
            <p class="form-label">Estado Actual: 
                <span class="badge bg-{{
                    $equipoPatron->estado == 'CUMPLE' ? 'success' : (
                    $equipoPatron->estado == 'CUMPLE PARCIALMENTE' ? 'warning text-dark' : 'danger')
                }}">
                    {{ $equipoPatron->estado }}
                </span>
            </p>
        </div>

        <button type="submit" class="btn btn-primary">Actualizar Equipo</button>
        <a href="{{ route('admin.equipos-patrones.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
