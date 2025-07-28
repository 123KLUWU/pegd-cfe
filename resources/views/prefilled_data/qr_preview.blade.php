{{-- resources/views/documents/customize_form.blade.php --}}
@extends('layouts.app')

@section('title', 'Generar Documento: ' . $template->name)

@section('content')
<div class="container mt-5">
    <a href="{{ route('templates.index') }}" class="btn btn-secondary mb-3">← Volver a Plantillas</a>

    <h1>Generar Documento: {{ $template->name }}</h1>
    <p class="lead">{{ $template->description ?? 'Esta plantilla sirve para la generación de documentos.' }}</p>

    <div class="alert alert-info" role="alert">
        <strong>Nota:</strong> Los documentos generados serán públicos y editables por cualquier persona con el enlace durante 3 horas, después se harán privados.
    </div>

    @if (session('error'))
        <div class="alert alert-danger mt-3" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- Opciones de Generación --}}
    <div class="row">
        <div class="col-md-6">
            <div class="card h-100 mb-3">
                <form action="{{ route('documents.generate.predefined') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="prefilled_data_id" value="{{ $data->id }}">
                    <button type="submit" class="btn btn-success btn-sm mb-2 me-2" target="_blank" >Generar Documento</button>
                </form>
                <div class="card-body text-center">
                    <h5 class="card-title">Generar</h5>

                    <p class="card-text">Crea una copia limpia de la plantilla original.</p>
                            {{-- Botón para Generar Documento con este formato prellenado --}}
                    <form action="{{ route('documents.generate.predefined') }}" method="POST" 
                    class="d-inline">
                        @csrf
                        <input type="hidden" name="prefilled_data_id" value="{{ $data->id }}">
                        <button type="submit" class="btn btn-success btn-sm mb-2 me-2" target="_blank" >Generar Documento</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection