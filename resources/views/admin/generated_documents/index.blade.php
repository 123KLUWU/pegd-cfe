{{-- resources/views/admin/generated_documents/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestión de Documentos Generados (Admin)')

@section('content')
<div class="container mt-5">
    <h1>Documentos Generados</h1>

    @if (session('success'))
        <div class="alert alert-success mt-3" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mt-3" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- Formulario de Búsqueda y Filtros --}}
    <form method="GET" action="{{ route('generated-documents.index') }}" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="search" class="form-label">Buscar:</label>
                <input type="text" class="form-control" id="search" name="search" value="{{ $search_query }}" placeholder="Título, usuario, plantilla...">
            </div>
            <div class="col-md-2">
                <label for="type" class="form-label">Tipo:</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Todos</option>
                    <option value="docs" {{ $selected_type == 'docs' ? 'selected' : '' }}>Google Docs</option>
                    <option value="sheets" {{ $selected_type == 'sheets' ? 'selected' : '' }}>Google Sheets</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="visibility" class="form-label">Visibilidad:</label>
                <select class="form-select" id="visibility" name="visibility">
                    <option value="">Todos</option>
                    <option value="public_editable" {{ $selected_visibility == 'public_editable' ? 'selected' : '' }}>Público Editable</option>
                    <option value="public_viewable" {{ $selected_visibility == 'public_viewable' ? 'selected' : '' }}>Público Ver</option>
                    <option value="private_restricted" {{ $selected_visibility == 'private_restricted' ? 'selected' : '' }}>Privado</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Estado DB:</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Activos</option>
                    <option value="trashed" {{ $selected_status == 'trashed' ? 'selected' : '' }}>Eliminados (Soft)</option>
                    <option value="with_trashed" {{ $selected_status == 'with_trashed' ? 'selected' : '' }}>Todos</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">Aplicar</button>
            </div>
        </div>
    </form>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Miniatura</th>
                <th>Título</th>
                <th>Generado Por</th>
                <th>Plantilla</th>
                <th>Tipo</th>
                <th>Visibilidad</th>
                <th>Fecha Generación</th>
                <th>Estado DB</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($documents as $document)
                <tr>
                    <td>{{ $document->id }}</td>
                    <td>
                        @if($document->thumbnail_link)
                            <img src="{{ $document->thumbnail_link }}" alt="Miniatura" style="width: 50px; height: 50px; object-fit: cover;">
                        @else
                            <span class="text-muted small">N/A</span>
                        @endif
                    </td>
                    <td>{{ $document->title }}</td>
                    <td>{{ $document->user->name ?? 'N/A' }}</td>
                    <td>{{ $document->template->name ?? 'N/A' }}</td>
                    <td>{{ ucfirst($document->type) }}</td>
                    <td>
                        <span class="badge bg-{{
                            $document->visibility_status == 'public_editable' ? 'success' : (
                            $document->visibility_status == 'public_viewable' ? 'info' : 'secondary')
                        }}">
                            {{ ucfirst(str_replace('_', ' ', $document->visibility_status)) }}
                        </span>
                    </td>
                    <td>{{ $document->generated_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @if ($document->trashed())
                            <span class="badge bg-secondary">Eliminado</span>
                        @else
                            <span class="badge bg-primary">Normal</span>
                        @endif
                    </td>
                    <td>
                        {{-- Botón "Ver/Editar" en Drive --}}
                        <a href="https://docs.google.com/{{ $document->type }}/d/{{ $document->google_drive_id }}/edit" target="_blank" class="btn btn-sm btn-info">Ver/Editar</a>
                        
                        {{-- Botón "Ver Detalles" (nuestra vista show) --}}
                        <a href="{{ route('generated-documents.show', $document->id) }}" class="btn btn-sm btn-outline-info">Detalles</a>

                        {{-- Selector de Visibilidad (en línea) --}}
                        <form action="{{ route('admin.generated-documents.change_visibility', $document->id) }}" method="POST" class="d-inline ms-1">
                            @csrf
                            <select name="visibility_status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                <option value="public_editable" {{ $document->visibility_status == 'public_editable' ? 'selected' : '' }}>P. Editable</option>
                                <option value="public_viewable" {{ $document->visibility_status == 'public_viewable' ? 'selected' : '' }}>P. Ver</option>
                                <option value="private_restricted" {{ $document->visibility_status == 'private_restricted' ? 'selected' : '' }}>Privado</option>
                            </select>
                        </form>

                        @if (!$document->trashed())
                            <form action="{{ route('generated-documents.destroy', $document->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar (soft delete) este documento?')">Eliminar</button>
                            </form>
                        @else
                            <form action="{{ route('admin.generated-documents.restore', $document->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Estás seguro de restaurar este documento?')">Restaurar</button>
                            </form>
                            <form action="{{ route('admin.generated-documents.force_delete', $document->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-dark btn-sm" onclick="return confirm('¡ADVERTENCIA! ¿Estás seguro de eliminar PERMANENTEMENTE este documento de la DB Y Google Drive? Esta acción es irreversible.')">Borrar Perm.</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">No se encontraron documentos generados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $documents->appends(request()->query())->links() }}
    </div>
</div>
@endsection