{{-- resources/views/documents/customize_form.blade.php --}}
@extends('layouts.app')

@section('title', 'Generar Documento: ' . $template->name)

@section('content')
<div class="container mt-5">
    <a href="{{ route('templates.index') }}" class="btn btn-secondary mb-3">← Volver a Plantillas</a>

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
            <h2 class="mb-3 text-center">{{ $template->name }}</h2>
            <p class="text-muted text-center">Plantilla: {{ $template->name ?? 'N/A' }} </p>
            <p class="text-center">{{ $template->description ?? 'Este formato no tiene descripción.' }}</p>

            <hr>


            <div class="alert alert-warning mt-4 text-center" role="alert">
                ¡Atención! Al hacer clic en "Generar Documento", se creará una copia del documento en Google Drive.
                <br>Será público y editable por cualquier persona con el enlace.
            </div>

            {{-- Botón para Generar Documento (Dispara la acción POST) --}}
            <form action="{{ route('documents.generate.blank') }}" method="POST" class="text-center">
                @csrf
                
                <div class="mb-3">
                    <label for="unidad_id_blank" class="form-label">Unidad:</label>
                    <select class="form-select @error('unidad_id') is-invalid @enderror" id="unidad_id_blank" name="unidad_id" required>
                        <option value="">Selecciona una Unidad</option>
                        @foreach($unidades as $unidad)
                            <option value="{{ $unidad->id }}" {{ old('unidad_id') == $unidad->id ? 'selected' : '' }}>{{ $unidad->unidad }}</option>
                        @endforeach
                    </select>
                    @error('unidad_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                {{-- Selector de Equipo Patrón --}}
                <div class="mb-3">
                    <label for="equipo_patron_id_preview" class="form-label">Equipo Patrón:</label>
                    <select class="form-select @error('equipo_patron_id') is-invalid @enderror" id="equipo_patron_id_preview" name="equipo_patron_id">
                        <option value="">Selecciona un Equipo Patrón (Opcional)</option>
                        @foreach($equiposPatrones as $equipo)
                            <option value="{{ $equipo->id }}" {{ old('equipo_patron_id') == $equipo->id ? 'selected' : '' }}>
                                {{ $equipo->identificador }} ({{ $equipo->marca ?? 'N/A' }} {{ $equipo->modelo ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                    @error('equipo_patron_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <input type="hidden" name="template_id" value="{{ $template->id }}">
                <button type="submit" class="btn btn-success btn-lg mt-3">Generar Documento Ahora</button>
            </form>

        </div>
    </div>
        {{-- Formulario de Personalización (inicialmente oculto, se muestra con JS) --}}
        <div class="mt-5 border p-4 rounded">
            {{-- Vista Detallada/Previsualización de Plantilla (Función 1) --}}
            <div class="card mb-4">
                <div class="card-header">
                    Previsualización de Plantilla (PDF)
                </div>
                <div class="card-body">
                    @if($template->pdf_file_path)
                        <div class="embed-responsive embed-responsive-16by9" style="height: 800px;">
                            {{-- ¡CAMBIO AQUÍ! Apuntar a la nueva ruta pública --}}
                            <iframe src="{{ route('templates.show_pdf_preview', $template->id) }}" width="100%" height="100%" style="border:none;"></iframe>
                        </div>
                        <p class="mt-2 text-center">
                            {{-- ¡CAMBIO AQUÍ! Apuntar a la nueva ruta pública --}}
                            <a href="{{ route('templates.show_pdf_preview', $template->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">Abrir PDF en nueva pestaña</a>
                        </p>
                    @else
                        <p class="text-warning">PDF de previsualización no disponible. Guarde/actualice la plantilla para generarlo.</p>
                    @endif
                </div>
            </div>
        </div>
</div>
@endsection