{{-- resources/views/user_documents/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Mis Documentos Generados')

@section('content')
<div class="container mt-5">
    <h1>Mis Documentos Generados</h1>

    @if (session('status'))
        <div class="alert alert-success mt-3" role="alert">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mt-3" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- Formulario de Búsqueda y Filtros --}}
    <form method="GET" action="{{ route('user.generated-documents.index') }}" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label">Buscar por Título/Plantilla:</label>
                <input type="text" class="form-control" id="search" name="search" value="{{ $search_query }}" placeholder="Buscar...">
            </div>
            <div class="col-md-3">
                <label for="type" class="form-label">Filtrar por Tipo:</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Todos los Tipos</option>
                    <option value="document" {{ $selected_type == 'document' ? 'selected' : '' }}>Google Docs</option>
                    <option value="spreadsheets" {{ $selected_type == 'spreadsheets' ? 'selected' : '' }}>Google Sheets</option>
                </select>
            </div>
            {{-- 
            <div class="col-md-3">
                <label for="visibility" class="form-label">Filtrar por Visibilidad:</label>
                <select class="form-select" id="visibility" name="visibility">
                    <option value="">Todas</option>
                    <option value="public_editable" {{ $selected_visibility == 'public_editable' ? 'selected' : '' }}>Público Editable</option>
                    <option value="public_viewable" {{ $selected_visibility == 'public_viewable' ? 'selected' : '' }}>Público Ver</option>
                    <option value="private_restricted" {{ $selected_visibility == 'private_restricted' ? 'selected' : '' }}>Privado</option>
                </select>
            </div>
             --}}
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="row">
        @forelse ($documents as $document)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $document->title }}</h5>
                        <p class="card-text"><small class="text-muted">Plantilla: {{ $document->template->name ?? 'N/A' }}</small></p>
                        <p class="card-text"><small class="text-muted">Generado el: {{ $document->generated_at->format('d/m/Y H:i') }}</small></p>
                        {{--
                        <p class="card-text">
                            <span class="badge bg-{{
                                $document->visibility_status == 'public_editable' ? 'success' : (
                                $document->visibility_status == 'public_viewable' ? 'info' : 'secondary')
                            }}">
                                {{ ucfirst(str_replace('_', ' ', $document->visibility_status)) }}
                            </span>
                        </p>
                        --}}

                        {{-- Miniatura del Documento --}}
                        <div class="document-thumbnail-container text-center my-3" style="width: 100%; height: 120px; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid #eee; border-radius: .25rem;">
                            @if($document->thumbnail_link)
                                <img src="{{ $document->thumbnail_link }}" alt="Miniatura de {{ $document->title }}" 
                                     class="img-fluid rounded" 
                                     style="width: 100%; height: 100%; object-fit: cover; object-position: top;">
                            @else
                                <div class="text-muted small">Miniatura no disponible</div>
                            @endif
                        </div>

                        <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center">
                            {{-- Botón Principal: Ver Documento en Drive --}}
                            <a href="https://docs.google.com/{{ $document->type }}/d/{{ $document->google_drive_id }}/edit" target="_blank" class="btn btn-primary btn-sm mb-2 me-2 flex-grow-1">Ver Documento</a>

                            {{-- Botón "3 Puntitos" (Dropdown de Opciones Secundarias) --}}
                            <div class="dropdown mb-2">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton_{{ $document->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Más Opciones</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                                        <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                    </svg>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton_{{ $document->id }}">
                                    {{-- Opción: Ver Detalles (nuestra vista show) --}}
                                    <li><a class="dropdown-item" href="{{ route('user.generated-documents.show', $document->id) }}">Ver Detalles</a></li>
                                    
                                    {{-- Opción: Ver Datos JSON (siempre útil para depuración/referencia)
                                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#dataJsonModal" data-json="{{ json_encode($document->data_values_json, JSON_PRETTY_PRINT) }}" data-title="{{ $document->title }}">Ver Datos JSON</button></li>
                                     --}}

                                    {{-- Opción: Eliminar (Soft Delete) --}}
                                    @if (!$document->trashed())
                                        <li>
                                            <form action="{{ route('user.generated-documents.destroy', $document->id) }}" method="POST" class="dropdown-item">
                                                @csrf
                                                <button type="submit" class="btn btn-link text-danger p-0 border-0" onclick="return confirm('¿Estás seguro de eliminar (soft delete) este documento?')">Eliminar</button>
                                            </form>
                                        </li>
                                    @else
                                        <li>
                                            <form action="{{ route('user.generated-documents.restore', $document->id) }}" method="POST" class="dropdown-item">
                                                @csrf
                                                <button type="submit" class="btn btn-link text-success p-0 border-0" onclick="return confirm('¿Estás seguro de restaurar este documento?')">Restaurar</button>
                                            </form>
                                        </li>
                                    @endif
                                    {{-- Opcional: Imprimir QR (si se implementa para documentos generados) --}}
                                    {{-- <li><a class="dropdown-item" href="{{ route('documents.generate_qr_pdf', $document->id) }}" target="_blank">Imprimir QR</a></li> --}}
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info" role="alert">
                    No has generado documentos aún, o no se encontraron con los criterios de búsqueda/filtro.
                </div>
            </div>
        @endforelse
    </div>

    {{-- Paginación --}}
    <div class="d-flex justify-content-center">
        {{ $documents->appends(request()->query())->links() }}
    </div>
</div>

<!-- Modal para mostrar el JSON de Datos -->
<div class="modal fade" id="dataJsonModal" tabindex="-1" aria-labelledby="dataJsonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dataJsonModalLabel">Datos de Prellenado para: <span id="dataJsonDocumentTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="dataJsonContent" class="bg-light p-3 rounded small"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lógica para el modal de datos JSON
    const dataJsonModalElement = document.getElementById('dataJsonModal');
    if (dataJsonModalElement) {
        dataJsonModalElement.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Botón que disparó el modal
            const dataJson = button.getAttribute('data-json');
            const docTitle = button.getAttribute('data-title');

            const modalTitle = dataJsonModalElement.querySelector('#dataJsonDocumentTitle');
            const modalBodyContent = dataJsonModalElement.querySelector('#dataJsonContent');

            modalTitle.textContent = docTitle;
            modalBodyContent.textContent = dataJson; // Ya viene pre-formateado con JSON_PRETTY_PRINT
        });
    }
});
</script>
@endpush
@endsection