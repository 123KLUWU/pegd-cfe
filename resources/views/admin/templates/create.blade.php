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

    <form action="{{ route('admin.templates.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Nombre de la Plantilla:</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="google_drive_id" class="form-label">ID de Google Drive:</label>
            <input type="text" class="form-control @error('google_drive_id') is-invalid @enderror" id="google_drive_id" name="google_drive_id" value="{{ old('google_drive_id') }}" placeholder="Ej: 1ABCDEF12345GHIJKL67890">
            @error('google_drive_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="type" class="form-label">Tipo de Plantilla:</label>
            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                <option value="">Selecciona un tipo</option>
                <option value="docs" {{ old('type') == 'docs' ? 'selected' : '' }}>Google Docs</option>
                <option value="sheets" {{ old('type') == 'sheets' ? 'selected' : '' }}>Google Sheets</option>
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

        {{-- INICIO: CAMPOS DINÁMICOS PARA MAPPING_RULES_JSON --}}
        <div class="mb-3">
            <label class="form-label">Reglas de Mapeo (Clave Lógica: Ubicación en Plantilla):</label>
            <div id="key-value-fields-container-mapping">
                {{-- Aquí se añadirán dinámicamente los pares clave-valor --}}
                {{-- Siempre muestra al menos un par vacío al crear --}}
                <div class="row mb-2 key-value-row">
                    <div class="col-5">
                        <input type="text" name="dynamic_keys[]" class="form-control" placeholder="Clave Lógica (ej. rpe_empleado)">
                    </div>
                    <div class="col-5">
                        <input type="text" name="dynamic_values[]" class="form-control" placeholder="Ubicación (ej. C8 )">
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-danger btn-sm remove-row-btn">X</button>
                    </div>
                </div>
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