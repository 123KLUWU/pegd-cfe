{{-- resources/views/documents/customize_form.blade.php --}}
@extends('layouts.app')

@section('title', 'Personalizar Documento')

@section('content')
<div class="container mt-5">
    <h1>Personalizar Plantilla: {{ $template->name }}</h1>
    <p>Ingresa los datos que deseas usar para prellenar tu documento.</p>

    @if (session('error'))
        <div class="alert alert-danger mt-3" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('documents.generate.custom') }}" method="POST">
        @csrf
        <input type="hidden" name="template_id" value="{{ $template->id }}">

        {{-- Campos de ejemplo para personalización --}}
        <div class="mb-3">
            <label for="tag_instrumento" class="form-label">Tag de Instrumento:</label>
            <input type="text" class="form-control @error('tag_instrumento') is-invalid @enderror" id="tag_instrumento" name="tag_instrumento" value="{{ old('tag_instrumento') }}">
            @error('tag_instrumento')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="rango_min_operativo" class="form-label">Rango Mínimo Operativo:</label>
            <input type="number" step="any" class="form-control @error('rango_min_operativo') is-invalid @enderror" id="rango_min_operativo" name="rango_min_operativo" value="{{ old('rango_min_operativo') }}">
            @error('rango_min_operativo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="observaciones" class="form-label">Observaciones:</label>
            <textarea class="form-control @error('observaciones') is-invalid @enderror" id="observaciones" name="observaciones" rows="3">{{ old('observaciones') }}</textarea>
            @error('observaciones')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Puedes añadir más campos aquí, o hacerlos dinámicos con JavaScript si es muy variado --}}
        {{-- Los nombres de estos campos (ej., 'tag_instrumento') serán las claves en el JSON de datos ($dataForFilling) --}}

        <button type="submit" class="btn btn-success">Generar Documento Personalizado</button>
        <a href="{{ route('templates.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection