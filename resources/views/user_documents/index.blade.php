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

                            <a  href="#"
                                class="btn btn-sm btn-outline-primary mb-2 js-send-doc"
                                data-bs-toggle="modal"
                                data-bs-target="#sendDocModal"
                                data-action="{{ route('emails.send', $document->id) }}"
                                data-document-title="{{ $document->title }}"
                                data-default-subject="Documento PEGD"
                                data-default-message="Hola,\n\nTe comparto el documento adjunto.\n\nSaludos.">
                                Enviar adjunto
                            </a>

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

<!-- Modal: Enviar adjunto -->
<div class="modal fade" id="sendDocModal" tabindex="-1" aria-labelledby="sendDocModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="sendDocForm" method="POST" action="">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title" id="sendDocModalLabel">Enviar adjunto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
  
          <div class="modal-body">
            <div class="mb-2">
              <label class="form-label">Documento</label>
              <input type="text" id="docTitleField" class="form-control" value="" readonly>
            </div>
  
            <div class="mb-2">
              <label class="form-label">Destinatarios</label>
              <input type="text" name="destinatarios" class="form-control" placeholder="persona@cfe.mx,otro@cfe.mx" required>
              <small class="text-muted">Separa varios con comas.</small>
            </div>
  
            <div class="mb-2">
              <label class="form-label">Asunto</label>
              <input type="text" id="subjectField" name="asunto" class="form-control" required>
            </div>
  
            <div class="mb-2">
              <label class="form-label">Mensaje</label>
              <textarea id="messageField" name="mensaje" rows="5" class="form-control"></textarea>
            </div>
  
            <small class="text-muted">* El archivo se exporta/descarga de Drive automáticamente y se adjunta al correo.</small>
          </div>
  
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success" id="sendBtn">Enviar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('sendDocForm');
  const docTitleField = document.getElementById('docTitleField');
  const subjectField = document.getElementById('subjectField');
  const messageField = document.getElementById('messageField');

  // Al hacer click en el botón, inyectamos action y defaults
  document.querySelectorAll('.js-send-doc').forEach(btn => {
    btn.addEventListener('click', function () {
      form.action = btn.dataset.action || '';
      docTitleField.value = btn.dataset.documentTitle || '';
      subjectField.value = btn.dataset.defaultSubject || 'Documento PEGD';
      messageField.value = btn.dataset.defaultMessage || 'Te comparto el documento adjunto.';
    });
  });

  // (Opcional) UX: deshabilitar botón Enviar para evitar doble submit
  const sendBtn = document.getElementById('sendBtn');
  if (sendBtn) {
    form.addEventListener('submit', function () {
      sendBtn.disabled = true;
      sendBtn.textContent = 'Enviando...';
    });
  }
});
</script>
@endpush