{{-- resources/views/admin/templates/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestión de Plantillas (Admin)')

@section('content')
<div class="container mt-5">
    <h1>Gestión de Plantillas</h1>

    <a href="{{ route('admin.templates.create') }}" class="btn btn-success mb-3">Crear Nueva Plantilla</a>

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
    <form method="GET" action="{{ route('admin.templates.index') }}" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="search" class="form-label">Buscar por Nombre/Descripción:</label>
                <input type="text" class="form-control" id="search" name="search" value="{{ $search_query }}" placeholder="Buscar...">
            </div>
            <div class="col-md-2">
                <label for="type" class="form-label">Tipo:</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Todos</option>
                    <option value="document" {{ $selected_type == 'document' ? 'selected' : '' }}>Google Docs</option>
                    <option value="spreadsheets" {{ $selected_type == 'spreadsheets' ? 'selected' : '' }}>Google spreadsheets</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="category_id" class="form-label">Categoría:</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ $selected_category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Estado:</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Activas</option>
                    <option value="active" {{ $selected_status == 'active' ? 'selected' : '' }}>Activas</option>
                    <option value="inactive" {{ $selected_status == 'inactive' ? 'selected' : '' }}>Inactivas</option>
                    <option value="trashed" {{ $selected_status == 'trashed' ? 'selected' : '' }}>Eliminadas (Soft)</option>
                    <option value="with_trashed" {{ $selected_status == 'with_trashed' ? 'selected' : '' }}>Todas (act. + elim.)</option>
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
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Categoría</th>
                <th>Activa</th>
                <th>Mapeo</th> {{-- Indicador de Mapeo --}}
                <th>Generaciones</th> {{-- Contador de Generaciones --}}
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($templates as $template)
                <tr>
                    <td>{{ $template->id }}</td>
                    <td>{{ $template->name }}</td>
                    <td>{{ ucfirst($template->type) }}</td>
                    <td>{{ $template->category->name ?? 'N/A' }}</td>
                    <td>
                        @if($template->is_active) <span class="badge bg-success">Sí</span>
                        @else <span class="badge bg-danger">No</span>
                        @endif
                    </td>
                    <td>
                        {{-- Indicador Visual de Marcadores/Reglas de Mapeo (Función 3) --}}
                        @if($template->mapping_rules_json && !empty($template->mapping_rules_json))
                            <span class="badge bg-success">Configurado</span>
                        @else
                            <span class="badge bg-warning text-dark">Pendiente</span>
                        @endif
                    </td>
                    <td>
                        {{-- Información de Uso (Generaciones) (Función 7) --}}
                        {{ $template->generated_documents_count ?? $template->generatedDocuments()->count() }}
                        <a href="{{ route('admin.templates.show', $template->id) }}#generated-docs-section" class="btn btn-sm btn-outline-info ms-2">Ver Lista</a>
                    </td>
                    <td>
                        {{-- Botones de Acciones --}}
                        <a href="{{ route('admin.templates.show', $template->id) }}" class="btn btn-sm btn-info">Ver Detalles</a>
                        <a href="{{ route('admin.templates.edit', $template->id) }}" class="btn btn-sm btn-warning">Editar</a>
                        <a href="{{ route('admin.templates.prefilled-data.create', $template->id) }}" class="btn btn-sm btn-primary">Datos Prefill</a>
                        
                        {{-- Duplicar Plantilla (Función 5) --}}
                        <form action="{{ route('admin.templates.duplicate', $template->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm('¿Estás seguro de duplicar esta plantilla?')">Duplicar</button>
                        </form>

                        @if (!$template->trashed())
                            <form action="{{ route('admin.templates.destroy', $template->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar (soft delete) esta plantilla?')">Eliminar</button>
                            </form>
                        @else
                            <form action="{{ route('admin.templates.restore', $template->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('¿Estás seguro de restaurar esta plantilla?')">Restaurar</button>
                            </form>
                            <form action="{{ route('admin.templates.force_delete', $template->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-dark" onclick="return confirm('¡ADVERTENCIA! ¿Estás seguro de eliminar PERMANENTEMENTE esta plantilla? Esta acción es irreversible y borrará el PDF de previsualización.')">Borrar Perm.</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No se encontraron plantillas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $templates->appends(request()->query())->links() }}
    </div>
</div>
@endsection
