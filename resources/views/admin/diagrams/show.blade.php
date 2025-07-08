{{-- resources/views/admin/diagrams/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detalles de Diagrama/Manual: ' . $diagram->name)

@section('content')
<div class="container mt-5">
    <a href="{{ route('admin.diagrams.index') }}" class="btn btn-secondary mb-3">← Volver a Gestión de Diagramas</a>

    <h1>Detalles de Diagrama/Manual: {{ $diagram->name }}</h1>

    <div class="row">
        <div class="col-md-8">
            {{-- Función 3.1: Visualización Directa --}}
            <h2 class="mt-4">Visualización del Archivo</h2>
            <div class="file-viewer mb-4" style="height: 600px; border: 1px solid #ddd; overflow: auto;">
                @if(Str::endsWith($diagram->file_path, ['.pdf', '.PDF']))
                    <iframe src="{{ route('diagrams.serve_file', $diagram->id) }}" width="100%" height="100%" style="border:none;"></iframe>
                @elseif(Str::endsWith($diagram->file_path, ['.png', '.jpg', '.jpeg', '.gif', '.svg', '.PNG', '.JPG', '.JPEG', '.GIF', '.SVG']))
                    <img src="{{ route('diagrams.serve_file', $diagram->id) }}" alt="{{ $diagram->name }}" style="max-width: 100%; height: auto; display: block; margin: auto;">
                @else
                    <p class="text-warning">Tipo de archivo no soportado para visualización en línea. <a href="{{ route('diagrams.serve_file', $diagram->id) }}" target="_blank">Descargar archivo</a></p>
                @endif
            </div>
            
            {{-- Función 3.1: Descargar --}}
            <a href="{{ route('diagrams.serve_file', $diagram->id) }}" class="btn btn-primary mb-4" target="_blank">Descargar Archivo</a>
        </div>

        <div class="col-md-4">
            <h2 class="mt-4">Detalles y Acciones</h2>
            <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item"><strong>Nombre Original:</strong> {{ $diagram->file_original_name }}</li>
                <li class="list-group-item"><strong>Tipo:</strong> {{ ucfirst($diagram->type) }}</li>
                <li class="list-group-item"><strong>Categoría:</strong> {{ $diagram->machine_category ?? 'N/A' }}</li>
                <li class="list-group-item"><strong>Descripción:</strong> {{ $diagram->description ?? 'N/A' }}</li>
                <li class="list-group-item"><strong>Activo:</strong> @if($diagram->is_active) Sí @else No @endif</li>
                <li class="list-group-item"><strong>Subido Por:</strong> {{ $diagram->createdBy->name ?? 'N/A' }}</li>
                <li class="list-group-item"><strong>Fecha de Subida:</strong> {{ $diagram->created_at->format('d/m/Y H:i') }}</li>
                <li class="list-group-item"><strong>Última Actualización:</strong> {{ $diagram->updated_at->format('d/m/Y H:i') }}</li>
            </ul>

            {{-- Función 3.2: Previsualización de QR --}}
            <h3 class="mt-4">Código QR para Impresión</h3>
            <div class="qr-code-admin-preview text-center mb-4">
                {{-- $qrSvg viene del controlador show() --}}
                <img src="data:image/svg+xml;base64,{!! base64_encode($qrSvg) !!}" alt="Código QR" style="max-width: 200px; height: auto;">
                <p class="mt-2"><small>{{ $qrContentUrl }}</small></p>
            </div>
            <a href="{{ route('diagrams.generate_qr_pdf', $diagram->id) }}" class="btn btn-success btn-sm w-100" target="_blank">Generar QR en PDF</a>

            <hr>

            <div class="d-flex justify-content-between mt-3">
                <a href="{{ route('admin.diagrams.edit', $diagram->id) }}" class="btn btn-warning">Editar Metadatos</a>
                @if (!$diagram->trashed())
                    <form action="{{ route('admin.diagrams.destroy', $diagram->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar (soft delete) este elemento?')">Eliminar</button>
                    </form>
                @else
                    <form action="{{ route('admin.diagrams.restore', $diagram->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('¿Estás seguro de restaurar este elemento?')">Restaurar</button>
                    </form>
                    <form action="{{ route('admin.diagrams.force_delete', $diagram->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-dark" onclick="return confirm('¡ADVERTENCIA! ¿Estás seguro de eliminar PERMANENTEMENTE este elemento y su archivo físico?')">Borrar Perm.</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection