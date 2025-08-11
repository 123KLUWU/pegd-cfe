{{-- resources/views/user_documents/index_by_unit_instrument.blade.php --}}
@extends('layouts.app')

@section('title', 'Mis Documentos por Unidad e Instrumento')

@section('content')
<div class="container mt-5">
    <h1>Mis Documentos por Unidad e Instrumento</h1>

    <a href="{{ route('user.generated-documents.index') }}" class="btn btn-secondary mb-3">← Volver a Vista Plana</a>

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

    {{-- Puedes añadir un formulario de búsqueda/filtro aquí si es necesario --}}
    {{-- Por ahora, mostramos todos los documentos agrupados --}}

    @forelse ($groupedDocuments as $unidadName => $documentsByUnit)
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Unidad: {{ $unidadName }}</h4>
            </div>
            <div class="card-body p-0">
                @forelse ($documentsByUnit as $instrumentoTag => $documentsByInstrument)
                    @php
                        // Obtener una instancia del instrumento para mostrar más detalles
                        // Asumimos que todos los documentos en este grupo tienen el mismo instrumento
                        $instrumento = $documentsByInstrument->first()->instrumento;
                    @endphp
                    <div class="card m-3 border">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Instrumento (TAG): {{ $instrumentoTag ?? 'Sin Instrumento Asociado' }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @forelse ($documentsByInstrument as $document)
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100 shadow-sm">
                                            <div class="card-body">
                                                <h6 class="card-title">{{ Str::limit($document->title, 40) }}</h6>
                                                <p class="card-text"><small class="text-muted">Generado el: {{ $document->generated_at->format('d/m/Y H:i') }}</small></p>
                                                <p class="card-text">
                                                    <span class="badge bg-{{
                                                        $document->visibility_status == 'public_editable' ? 'success' : (
                                                        $document->visibility_status == 'public_viewable' ? 'info' : 'secondary')
                                                    }}">
                                                        {{ ucfirst(str_replace('_', ' ', $document->visibility_status)) }}
                                                    </span>
                                                </p>

                                                {{-- Miniatura del Documento --}}
                                                <div class="document-thumbnail-container text-center my-2" style="width: 100%; height: 100px; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid #eee; border-radius: .25rem;">
                                                    @if($document->thumbnail_link)
                                                        <img src="{{ $document->thumbnail_link }}" alt="Miniatura" class="img-fluid rounded" style="width: 100%; height: 100%; object-fit: cover; object-position: top;">
                                                    @else
                                                        <div class="text-muted small">Miniatura no disp.</div>
                                                    @endif
                                                </div>

                                                <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center">
                                                    <a href="https://docs.google.com/{{ $document->type }}s/d/{{ $document->google_drive_id }}/edit" target="_blank" class="btn btn-primary btn-sm flex-grow-1 me-1">Ver Doc</a>
                                                    <div class="dropdown">
                                                        <button class="btn btn-outline-secondary dropdown-toggle btn-sm" type="button" id="docOptions_{{ $document->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <span class="visually-hidden">Más Opciones</span>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16"><path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/></svg>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="docOptions_{{ $document->id }}">
                                                            <li><a class="dropdown-item" href="{{ route('user.generated-documents.show', $document->id) }}">Ver Detalles</a></li>
                                                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#dataJsonModal" data-json="{{ json_encode($document->data_values_json, JSON_PRETTY_PRINT) }}" data-title="{{ $document->title }}">Ver Datos JSON</button></li>
                                                            @if (!$document->trashed())
                                                                <li>
                                                                    <form action="{{ route('user.generated-documents.destroy', $document->id) }}" method="POST" class="dropdown-item">
                                                                        @csrf
                                                                        <button type="submit" class="btn btn-link text-danger p-0 border-0" onclick="return confirm('¿Eliminar este documento?')">Eliminar</button>
                                                                    </form>
                                                                </li>
                                                            @else
                                                                <li>
                                                                    <form action="{{ route('user.generated-documents.restore', $document->id) }}" method="POST" class="dropdown-item">
                                                                        @csrf
                                                                        <button type="submit" class="btn btn-link text-success p-0 border-0" onclick="return confirm('¿Restaurar este documento?')">Restaurar</button>
                                                                    </form>
                                                                </li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12"><p class="text-muted m-3">No hay documentos generados para este instrumento.</p></div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted m-3">No hay instrumentos con documentos generados en esta unidad.</p>
                @endforelse
            </div>
        </div>
    @empty
        <div class="alert alert-info" role="alert">
            No has generado documentos aún, o no se encontraron documentos asociados a unidades e instrumentos.
        </div>
    @endforelse

    {{-- Modal para mostrar el JSON de Datos (reutilizado) --}}
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
        // Lógica para el modal de datos JSON (reutilizado)
        const dataJsonModalElement = document.getElementById('dataJsonModal');
        if (dataJsonModalElement) {
            dataJsonModalElement.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Botón que disparó el modal
                const dataJson = button.getAttribute('data-json');
                const docTitle = button.getAttribute('data-title');

                const modalTitle = dataJsonModalElement.querySelector('#dataJsonDocumentTitle');
                const modalBodyContent = dataJsonModalElement.querySelector('#dataJsonContent');

                modalTitle.textContent = docTitle;
                modalBodyContent.textContent = dataJson;
            });
        }
    });
    </script>
    @endpush
</div>
@endsection
