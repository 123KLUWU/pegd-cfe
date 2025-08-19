{{-- resources/views/admin/template_prefilled_data/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Editar Formato Predeterminado {{ $prefilledData->name }}</h1>

    <form method="POST" action="{{ route('admin.templates.prefilled-data.update', $prefilledData->id) }}">
        @csrf
        @method('PUT')
        
        <input type="hidden" name="template_id" value="{{ $prefilledData->id }}">

        <div class="mb-3">
            <label for="name" class="form-label">Nombre del Formato:</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Descripción:</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description">{{ old('description') }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="data_json_raw" class="form-label">Datos JSON (Clave:Valor, ej: {"rpe": "12345", "rango_min": "0"}):</label>
            <textarea class="form-control @error('data_json_raw') is-invalid @enderror" id="data_json_raw" name="data_json_raw" rows="8">{{ old('data_json_raw', isset($prefilledData) ? json_encode($prefilledData->data_json, JSON_PRETTY_PRINT) : '') }}</textarea>
            @error('data_json_raw')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Aquí iría la lógica para añadir campos dinámicos con JavaScript --}}
        {{-- Por ahora, con el textarea de JSON crudo es más sencillo --}}
        <div id="dynamic-fields-container">
            {{-- Example of a dynamic field pair (requires JS to add more) --}}
            {{-- <div class="row mb-2">
                <div class="col">
                    <input type="text" name="dynamic_data_keys[]" class="form-control" placeholder="Clave">
                </div>
                <div class="col">
                    <input type="text" name="dynamic_data_values[]" class="form-control" placeholder="Valor">
                </div>
            </div> --}}
        </div>
        {{-- 
        <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="add-dynamic-field">Añadir Campo</button>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="is_default_option" name="is_default_option" {{ old('is_default_option', isset($prefilledData) && $prefilledData->is_default_option ? 'checked' : '') }}>
            <label class="form-check-label" for="is_default_option">
                Marcar como opción predeterminada para esta plantilla
            </label>
        </div>
        --}}

        <button type="submit" class="btn btn-primary">Guardar Formato Predeterminado</button>
        <a href="{{ route('admin.templates.edit', $prefilledData->id) }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection