{{-- resources/views/user_documents/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detalles de Documento: ' . $generatedDocument->title)

@section('content')
<div class="container mt-5">
    <a href="{{ route('user.generated-documents.index') }}" class="btn btn-secondary mb-3">← Volver a Mis Documentos</a>

    <h1>Detalles de Documento: {{ $generatedDocument->title }}</h1>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    Información General
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>ID Interno:</strong> {{ $generatedDocument->id }}</li>
                    <li class="list-group-item"><strong>ID Google Drive:</strong> <a href="https://docs.google.com/{{ $generatedDocument->type }}/d/{{ $generatedDocument->google_drive_id }}/edit" target="_blank">{{ $generatedDocument->google_drive_id }}</a></li>
                    <li class="list-group-item"><strong>Generado Por:</strong> {{ $generatedDocument->user->name ?? 'N/A' }} (RPE: {{ $generatedDocument->user->rpe ?? 'N/A' }})</li>
                    <li class="list-group-item"><strong>Plantilla Usada:</strong> {{ $generatedDocument->template->name ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Tipo:</strong> {{ ucfirst($generatedDocument->type) }}</li>
                    <li class="list-group-item"><strong>Visibilidad:</strong> {{ ucfirst(str_replace('_', ' ', $generatedDocument->visibility_status)) }}</li>
                    <li class="list-group-item"><strong>Fecha Generación:</strong> {{ $generatedDocument->generated_at->format('d/m/Y H:i') }}</li>
                    <li class="list-group-item"><strong>Se hará privado a las:</strong> {{ $generatedDocument->make_private_at ? $generatedDocument->make_private_at->format('d/m/Y H:i') : 'No aplica' }}</li>
                    <li class="list-group-item"><strong>Estado DB:</strong> @if($generatedDocument->trashed()) Eliminado @else Normal @endif</li>
                </ul>
            </div>
            
            {{-- Botones de Acción --}}
            <div class="mb-3">
                <a href="https://docs.google.com/{{ $generatedDocument->type }}/d/{{ $generatedDocument->google_drive_id }}/edit" target="_blank" class="btn btn-info me-2">Abrir en Google Drive</a>
                @if (!$generatedDocument->trashed())
                    <form action="{{ route('user.generated-documents.destroy', $generatedDocument->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar (soft delete) este documento?')">Eliminar (Soft)</button>
                    </form>
                @else
                    <form action="{{ route('user.generated-documents.restore', $generatedDocument->id) }}" method="POST" class="d-inline me-2">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('¿Estás seguro de restaurar este documento?')">Restaurar</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="col-md-6">
            {{-- Miniatura del Documento --}}
            <div class="card mb-4">
                <div class="card-header">
                    Miniatura
                </div>
                <div class="card-body text-center">
                    @if($generatedDocument->thumbnail_link)
                        <img src="{{ $generatedDocument->thumbnail_link }}" alt="Miniatura de {{ $generatedDocument->title }}" class="img-fluid" style="max-width: 100%; height: auto; border: 1px solid #eee;">
                    @else
                        <div class="alert alert-warning">Miniatura no disponible.</div>
                    @endif
                </div>
            </div>

            {{-- Datos de Prellenado (data_values_json) --}}
            <div class="card mb-4">
                <div class="card-header">
                    Datos de Prellenado (data_values_json)
                </div>
                <div class="card-body">
                    @if($generatedDocument->data_values_json)
                        <pre class="bg-light p-3 rounded small">{{ json_encode($generatedDocument->data_values_json, JSON_PRETTY_PRINT) }}</pre>
                    @else
                        <p class="text-muted">Este documento no tiene datos de prellenado almacenados.</p>
                    @endif
                </div>
            </div>

            {{-- Historial de Actividad del Documento --}}
            <div class="card mb-4">
                <div class="card-header">
                    Actividad Relacionada con este Documento
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($documentActivities as $activity)
                        <li class="list-group-item">
                            <small class="text-muted">{{ $activity->created_at->format('d/m/Y H:i') }}</small><br>
                            <strong>{{ $activity->event }}:</strong> {{ $activity->description }}
                            @if($activity->properties->isNotEmpty())
                                <pre class="small text-muted mt-1">{{ json_encode($activity->properties->toArray(), JSON_PRETTY_PRINT) }}</pre>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item">No hay actividad registrada para este documento.</li>
                    @endforelse
                </ul>
                <div class="card-footer d-flex justify-content-center">
                    {{ $documentActivities->links('pagination::bootstrap-5', ['pageName' => 'activities_page']) }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
