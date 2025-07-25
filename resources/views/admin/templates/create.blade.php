{{-- resources/views/admin/templates/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Crear Nueva Plantilla')

@section('content')
<div class="container mt-5">
    <h1>Crear Nueva Plantilla</h1>

    @if (session('error'))
        <div class="alert alert-danger mt-3" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- ¡Asegúrate de añadir enctype="multipart/form-data" al formulario! --}}
    <form action="{{ route('admin.templates.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Nombre de la Plantilla:</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="type" class="form-label">Tipo de Plantilla:</label>
            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                <option value="">Selecciona un tipo</option>
                <option value="document" {{ old('type') == 'document' ? 'selected' : '' }}>Google Docs</option>
                <option value="spreadsheets" {{ old('type') == 'spreadsheets' ? 'selected' : '' }}>Google spreadsheets</option>
            </select>
            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="category_id" class="form-label">Categoría:</label>
            <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                <option value="">Sin Categoría</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Descripción:</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Opciones de Fuente de Plantilla --}}
        <div class="mb-3 border p-3 rounded">
            <p class="fw-bold">Fuente de la Plantilla:</p>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="source_option" id="source_id" value="id" {{ old('source_option', 'id') == 'id' ? 'checked' : '' }}>
                <label class="form-check-label" for="source_id">
                    Proporcionar ID de Google Drive existente
                </label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="radio" name="source_option" id="source_file" value="file" {{ old('source_option') == 'file' ? 'checked' : '' }}>
                <label class="form-check-label" for="source_file">
                    Subir un archivo (se subirá a Google Drive)
                </label>
            </div>

            <div id="google_drive_id_group" class="mb-3">
                <label for="google_drive_id" class="form-label">ID de Google Drive:</label>
                <input type="text" class="form-control @error('google_drive_id') is-invalid @enderror" id="google_drive_id" name="google_drive_id" value="{{ old('google_drive_id') }}" placeholder="Ej: 1ABCDEF12345GHIJKL67890">
                @error('google_drive_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div id="template_file_group" class="mb-3" style="display: none;">
                <label for="template_file" class="form-label">Subir Archivo (DOCX, XLSX, PDF):</label>
                <input type="file" class="form-control @error('template_file') is-invalid @enderror" id="template_file" name="template_file">
                <div class="form-text">Se aceptan .docx, .xlsx, .pdf. Se subirán a Google Drive.</div>
                @error('template_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        {{-- FIN: Opciones de Fuente de Plantilla --}}

        {{-- CAMPOS DINÁMICOS PARA MAPPING_RULES_JSON (igual que antes) --}}
        <div class="mb-3">
            <label class="form-label">Reglas de Mapeo (Clave Lógica: Ubicación en Plantilla):</label>
            <div id="key-value-fields-container-mapping">
                @php
                    $existingMappingRules = old('dynamic_keys', []);
                    $existingMappingValues = old('dynamic_values', []);
                @endphp

                @forelse (array_keys($existingMappingRules) as $index => $key)
                    <div class="row mb-2 key-value-row">
                        <div class="col-5">
                            <input type="text" name="dynamic_keys[]" class="form-control" placeholder="Clave Lógica" value="{{ $key }}">
                        </div>
                        <div class="col-5">
                            <input type="text" name="dynamic_values[]" class="form-control" placeholder="Ubicación" value="{{ $existingMappingValues[$index] ?? '' }}">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-danger btn-sm remove-row-btn">X</button>
                        </div>
                    </div>
                @empty
                    <div class="row mb-2 key-value-row">
                        <div class="col-5">
                            <input type="text" name="dynamic_keys[]" class="form-control" placeholder="Clave Lógica">
                        </div>
                        <div class="col-5">
                            <input type="text" name="dynamic_values[]" class="form-control" placeholder="Ubicación">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-danger btn-sm remove-row-btn">X</button>
                        </div>
                    </div>
                @endforelse
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-key-value-row-mapping">Añadir Regla</button>
            @error('dynamic_keys')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        {{-- FIN: CAMPOS DINÁMICOS --}}

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">
                Plantilla Activa (Disponible para usuarios)
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Guardar Plantilla</button>
        <a href="{{ route('admin.templates.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection