{{-- resources/views/admin/template_prefilled_data/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Crear Formato Predeterminado para {{ $template->name }}</h1>

    <form method="POST" action="{{ route('admin.templates.prefilled-data.store', $template->id) }}">
        @csrf

        <input type="hidden" name="template_id" value="{{ $template->id }}">

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

        {{-- Dentro de tu formulario, donde antes tenías el textarea del JSON --}}

<div class="mb-3">
    <label class="form-label">Reglas/Datos (Clave: Valor):</label>
    <div id="key-value-fields-container">
        {{-- Aquí se añadirán dinámicamente los pares clave-valor --}}

        {{-- Ejemplo de un par inicial o si ya tienes datos --}}
        @php
            // Para editar: Convierte el JSON a un array de pares clave-valor
            $existingData = isset($prefilledData) ? $prefilledData->data_json : (isset($template) ? $template->mapping_rules_json : []);
            // En este punto, $existingData debería ser un array asociativo PHP (ej. ['rpe_empleado' => 'C8'])
        @endphp

        @forelse ($existingData as $key => $value)
            <div class="row mb-2 key-value-row">
                <div class="col-5">
                    <input type="text" name="dynamic_keys[]" class="form-control" placeholder="Clave Lógica" value="{{ $key }}">
                </div>
                <div class="col-5">
                    <input type="text" name="dynamic_values[]" class="form-control" placeholder="Valor / Ubicación" value="{{ $value }}">
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-danger btn-sm remove-row-btn">X</button>
                </div>
            </div>
        @empty
            {{-- Si no hay datos existentes, muestra un par vacío --}}
            <div class="row mb-2 key-value-row">
                <div class="col-5">
                    <input type="text" name="dynamic_keys[]" class="form-control" placeholder="Clave Lógica">
                </div>
                <div class="col-5">
                    <input type="text" name="dynamic_values[]" class="form-control" placeholder="Valor / Ubicación">
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-danger btn-sm remove-row-btn">X</button>
                </div>
            </div>
        @endforelse
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-key-value-row">Añadir Par Clave-Valor</button>
</div>


        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="is_default_option" name="is_default_option" {{ old('is_default_option', isset($prefilledData) && $prefilledData->is_default_option ? 'checked' : '') }}>
            <label class="form-check-label" for="is_default_option">
                Marcar como opción predeterminada para esta plantilla
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Guardar Formato Predeterminado</button>
        <a href="{{ route('admin.templates.edit', $template->id) }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection