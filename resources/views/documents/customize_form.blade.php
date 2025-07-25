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
                <div class="card-body text-center">
                    <h5 class="card-title">Generar en Blanco</h5>
                    <p class="card-text">Crea una copia limpia de la plantilla original.</p>
                    <form action="{{ route('documents.generate.blank') }}" method="POST">
                        @csrf
                        <input type="hidden" name="template_id" value="{{ $template->id }}">
                        <button type="submit" class="btn btn-primary">Generar</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 mb-3">
                <div class="card-body text-center">
                    <h5 class="card-title">Personalizar Documento</h5>
                    <p class="card-text">Ingresa los datos exactos que deseas para tu documento.</p>
                    {{-- Este botón llevaría al formulario de personalización detallado --}}
                    <a href="#" class="btn btn-secondary" id="open-customize-form">Ir a Formulario</a>
                </div>
            </div>
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
@endsection