{{-- resources/views/diagrams/show.blade.php --}}
@extends('layouts.app')

@section('title', $diagram->name)

@section('content')
<div class="container mt-5">
    <a href="{{ route('diagrams.index') }}" class="btn btn-secondary mb-3">← Volver a Diagramas</a>

    <h1>{{ $diagram->name }}</h1>
    <p class="text-muted">Tipo: {{ ucfirst($diagram->type) }}</p>
    @if($diagram->machine_category)
        <p class="text-muted">Máquina: {{ $diagram->machine_category }}</p>
    @endif
    <p>{{ $diagram->description }}</p>

    <hr>

    {{-- Visualización del Archivo (Ejemplo: PDF o Imagen) --}}
    <h2 class="mt-4">Visualización del Archivo</h2>
    <div class="file-viewer mb-4" style="height: 600px; border: 1px solid #ddd; overflow: auto;">
        @if(Str::endsWith($diagram->file_path, ['.pdf', '.PDF']))
            {{-- Para PDFs, usar un visor de PDF --}}
            <iframe src="{{ route('diagrams.serve_file', $diagram->id) }}" width="100%" height="100%" style="border:none;"></iframe>
        @elseif(Str::endsWith($diagram->file_path, ['.png', '.jpg', '.jpeg', '.gif', '.svg', '.PNG', '.JPG', '.JPEG', '.GIF', '.SVG']))
            {{-- Para Imágenes --}}
            <img src="{{ route('diagrams.serve_file', $diagram->id) }}" alt="{{ $diagram->name }}" style="max-width: 100%; height: auto; display: block; margin: auto;">
        @else
            <p class="text-warning">Tipo de archivo no soportado para visualización en línea. <a href="{{ route('diagrams.serve_file', $diagram->id) }}" target="_blank">Descargar archivo</a></p>
        @endif
    </div>

    <hr>

    {{-- Sección del Código QR --}}
    <h2 class="mt-4" id="qr-section">Código QR para Acceso Rápido</h2>
    <p>Escanea este código QR con tu dispositivo móvil para acceder directamente a este diagrama/manual. (Requiere autenticación).</p>

    <div class="qr-code-container text-center mb-4">
        {{-- Genera el QR que apunta a la URL protegida del archivo real --}}
        {!! QrCode::size(250)->generate(route('diagrams.serve_file', $diagram->id)) !!}
    </div>

    <p class="text-center mt-3">
        <a href="{{ route('diagrams.generate_qr_pdf', $diagram->id) }}" class="btn btn-success btn-lg" target="_blank">Imprimir QR en PDF</a>
    </p>

    <p class="text-center">
        <a href="{{ route('diagrams.serve_file', $diagram->id) }}" target="_blank" class="btn btn-outline-primary">Enlace Directo al Archivo</a>
    </p>

    <hr>

    <h2 class="mt-4">Detalles del Archivo</h2>
    <ul class="list-group list-group-flush">
        <li class="list-group-item"><strong>Nombre Original:</strong> {{ $diagram->file_original_name }}</li>
        <li class="list-group-item"><strong>Subido por:</strong> {{ $diagram->createdBy->name ?? 'N/A' }}</li>
        <li class="list-group-item"><strong>Fecha de Subida:</strong> {{ $diagram->created_at->format('d/m/Y H:i') }}</li>
        <li class="list-group-item"><strong>Última Actualización:</strong> {{ $diagram->updated_at->format('d/m/Y H:i') }}</li>
    </ul>

</div>
@endsection