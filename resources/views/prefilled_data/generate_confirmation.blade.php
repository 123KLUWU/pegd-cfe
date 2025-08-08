{{-- resources/views/prefilled_data/generate_confirmation.blade.php --}}
@extends('layouts.app')

@section('title', 'Confirmar Generación: ' . $prefilledData->name)

@section('content')
<div class="container mt-5">
    <a href="{{ route('prefilled-data.index') }}" class="btn btn-secondary mb-3">← Volver a Formatos Predeterminados</a>
    
    @if (session('error'))
        <div class="alert alert-danger mt-3" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="card shadow-sm mx-auto">
        <div class="card-header bg-primary text-white text-center">
            <h1 class="card-title mb-0">Confirmar Generación de Documento</h1>
        </div>
        <div class="card-body">
            <h2 class="mb-3 text-center">{{ $prefilledData->name }}</h2>
            <p class="text-muted text-center">Plantilla: {{ $prefilledData->template->name ?? 'N/A' }} ({{ ucfirst($prefilledData->template->type ?? '') }})</p>
            <p class="text-center">{{ $prefilledData->description ?? 'Este formato no tiene descripción.' }}</p>

            <hr>

            {{-- 
            
            <h3 class="mb-3">Detalles del Formato:</h3>
            <div class="mb-3">
                <label class="form-label fw-bold">Datos a Prellenar (Ejemplo):</label>
                <pre class="bg-light p-3 rounded small" style="max-height: 200px; overflow-y: auto;">{{ json_encode($prefilledData->data_json, JSON_PRETTY_PRINT) }}</pre>
            </div>
             --}}

            <div class="alert alert-warning mt-4 text-center" role="alert">
                ¡Atención! Al hacer clic en "Generar Documento", se creará una copia del documento en Google Drive.
                <br>Será público y editable por cualquier persona con el enlace.
            </div>

            {{-- Botón para Generar Documento (Dispara la acción POST) --}}
            <form action="{{ route('documents.generate.predefined') }}" method="POST" class="text-center">
                @csrf
                             
                {{-- Selector de Unidad --}}
                <div class="mb-3">
                    <label for="unidad_id" class="form-label">Unidad (Obligatorio):</label>
                    <select class="form-select @error('unidad_id') is-invalid @enderror" id="unidad_id" name="unidad_id" required>
                        <option value="">Seleccione una Unidad</option>
                        @foreach($unidades as $unidad)
                            <option value="{{ $unidad->id }}" {{ old('unidad_id', $prefilledData->unidad_id ?? '') == $unidad->id ? 'selected' : '' }}>
                                {{ $unidad->unidad }}
                            </option>
                        @endforeach
                    </select>
                    @error('unidad_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <input type="hidden" name="prefilled_data_id" value="{{ $prefilledData->id }}">

                {{-- <input type="hidden" name="unidad_id" id="unidad_id_hidden" value="{{ old('unidad_id', $prefilledData->unidad_id ?? '') }}"> --}}
                
                <button type="submit" class="btn btn-success btn-lg mt-3">Generar Documento Ahora</button>
            </form>
        </div>

    </div>
</div>
@endsection