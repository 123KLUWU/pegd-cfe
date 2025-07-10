{{-- resources/views/admin/templates/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detalles de Plantilla: ' . $template->name)

@section('content')
<div class="container mt-5">
    <a href="{{ route('admin.templates.index') }}" class="btn btn-secondary mb-3">← Volver a Gestión de Plantillas</a>

    <h1>Detalles de Plantilla: {{ $template->name }}</h1>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    Información General
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>ID:</strong> {{ $template->id }}</li>
                    <li class="list-group-item"><strong>Nombre:</strong> {{ $template->name }}</li>
                    <li class="list-group-item"><strong>Tipo:</strong> {{ ucfirst($template->type) }}</li>
                    <li class="list-group-item"><strong>Categoría:</strong> {{ $template->category->name ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Descripción:</strong> {{ $template->description ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Activa:</strong> @if($template->is_active) Sí @else No @endif</li>
                    <li class="list-group-item"><strong>Creada Por:</strong> {{ $template->createdBy->name ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Fecha de Creación:</strong> {{ $template->created_at->format('d/m/Y H:i') }}</li>
                    <li class="list-group-item"><strong>Última Actualización:</strong> {{ $template->updated_at->format('d/m/Y H:i') }}</li>
                </ul>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    Reglas de Mapeo (mapping_rules_json)
                </div>
                <div class="card-body">
                    @if($template->mapping_rules_json)
                        <pre class="bg-light p-3 rounded small">{{ json_encode($template->mapping_rules_json, JSON_PRETTY_PRINT) }}</pre>
                    @else
                        <p class="text-muted">No hay reglas de mapeo configuradas para esta plantilla.</p>
                    @endif
                </div>
            </div>

            <a href="{{ route('admin.templates.edit', $template->id) }}" class="btn btn-warning me-2">Editar Plantilla</a>
            <a href="{{ route('admin.templates.duplicate', $template->id) }}" class="btn btn-secondary me-2">Duplicar Plantilla</a>
            <a href="{{ route('admin.templates.prefilled-data.create', $template->id) }}" class="btn btn-primary">Gestionar Datos Prefill</a>
        </div>

        <div class="col-md-6">
            {{-- Vista Detallada/Previsualización de Plantilla (Función 1) --}}
            <div class="card mb-4">
                <div class="card-header">
                    Previsualización de Plantilla (PDF)
                </div>
                <div class="card-body">
                    @if($template->pdf_file_path)
                        <div class="embed-responsive embed-responsive-16by9" style="height: 400px;">
                            {{-- ¡CAMBIO AQUÍ! Apuntar a la ruta de Laravel --}}
                            <iframe src="{{ route('admin.templates.serve_pdf_preview', $template->id) }}" width="100%" height="100%" style="border:none;"></iframe>
                        </div>
                        <p class="mt-2 text-center">
                            <a href="{{ route('admin.templates.serve_pdf_preview', $template->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">Abrir PDF en nueva pestaña</a>
                        </p>
                    @else
                        <p class="text-warning">PDF de previsualización no disponible. Guarde/actualice la plantilla para generarlo.</p>
                    @endif
                </div>
            </div>

            {{-- Historial de Actividad de Plantilla (Función 4) --}}
            <div class="card mb-4">
                <div class="card-header">
                    Historial de Actividad de Plantilla
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($templateActivities as $activity)
                        <li class="list-group-item">
                            <small class="text-muted">{{ $activity->created_at->format('d/m/Y H:i') }}</small><br>
                            <strong>{{ $activity->event }}:</strong> {{ $activity->description }}
                            @if($activity->properties->isNotEmpty())
                                <pre class="small text-muted mt-1">{{ json_encode($activity->properties->toArray(), JSON_PRETTY_PRINT) }}</pre>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item">No hay actividad registrada para esta plantilla.</li>
                    @endforelse
                </ul>
                <div class="card-footer d-flex justify-content-center">
                    {{ $templateActivities->links('pagination::bootstrap-5', ['pageName' => 'activities_page']) }}
                </div>
            </div>

            {{-- Información de Uso (Generaciones) (Función 7) --}}
            <div class="card mb-4">
                <div class="card-header">
                    Documentos Generados a partir de esta Plantilla
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($generatedDocs as $doc)
                        <li class="list-group-item">
                            <small class="text-muted">{{ $doc->created_at->format('d/m/Y H:i') }}</small><br>
                            <a href="https://docs.google.com/{{ $doc->type }}s/d/{{ $doc->google_drive_id }}/edit" target="_blank">{{ $doc->title }}</a>
                            ({{ ucfirst($doc->type) }}, {{ ucfirst(str_replace('_', ' ', $doc->visibility_status)) }})
                        </li>
                    @empty
                        <li class="list-group-item">Esta plantilla no ha generado documentos aún.</li>
                    @endforelse
                </ul>
                <div class="card-footer d-flex justify-content-center">
                    {{ $generatedDocs->links('pagination::bootstrap-5', ['pageName' => 'generated_docs_page']) }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
