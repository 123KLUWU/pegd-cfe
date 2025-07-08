{{-- resources/views/admin/diagrams/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestión de Diagramas y Manuales (Admin)')

@section('content')
<div class="container mt-5">
    <h1>Gestión de Diagramas y Manuales</h1>

    <a href="{{ route('admin.diagrams.create') }}" class="btn btn-success mb-3">Subir Nuevo Diagrama/Manual</a>

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
    <form method="GET" action="{{ route('admin.diagrams.index') }}" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label">Buscar:</label>
                <input type="text" class="form-control" id="search" name="search" value="{{ $search_query }}" placeholder="Nombre, descripción, categoría...">
            </div>
            <div class="col-md-2">
                <label for="type" class="form-label">Tipo:</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Todos</option>
                    <option value="diagram" {{ $selected_type == 'diagram' ? 'selected' : '' }}>Diagramas</option>
                    <option value="manual" {{ $selected_type == 'manual' ? 'selected' : '' }}>Manuales</option>
                </select>
            </div>
            {{--
            <div class="col-md-2">
                <label for="category" class="form-label">Categoría:</label>
                <select class="form-select" id="category" name="category">
                    <option value="">Todas</option>
                    @foreach ($availableCategories as $category)
                        <option value="{{ $category }}" {{ $selected_category == $category ? 'selected' : '' }}>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            --}}
            <div class="col-md-2">
                <label for="trashed" class="form-label">Estado:</label>
                <select class="form-select" id="trashed" name="trashed">
                    <option value="">Activos</option>
                    <option value="true" {{ request('trashed') == 'true' ? 'selected' : '' }}>Eliminados</option>
                    <option value="false" {{ request('with_trashed') == 'true' ? 'selected' : '' }}>Todos (activos + eliminados)</option>
                </select>
            </div>
            <div class="col-md-2">
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
                <th>Activo</th>
                <th>Subido Por</th>
                <th>Estado DB</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($diagrams as $diagram)
                <tr>
                    <td>{{ $diagram->id }}</td>
                    <td>{{ $diagram->name }}</td>
                    <td>{{ ucfirst($diagram->type) }}</td>
                    <td>{{ $diagram->machine_category ?? 'N/A' }}</td>
                    <td>
                        @if($diagram->is_active) <span class="badge bg-success">Sí</span>
                        @else <span class="badge bg-danger">No</span>
                        @endif
                    </td>
                    <td>{{ $diagram->createdBy->name ?? 'N/A' }}</td>
                    <td>
                        @if ($diagram->trashed())
                            <span class="badge bg-secondary">Eliminado</span>
                        @else
                            <span class="badge bg-primary">Normal</span>
                        @endif
                    </td>
                    <td>
                        {{-- Botones de Acciones --}}
                        <a href="{{ route('admin.diagrams.edit', $diagram->id) }}" class="btn btn-warning btn-sm">Editar</a>
                        <a href="{{ route('diagrams.show', $diagram->id) }}" class="btn btn-info btn-sm" target="_blank">Ver</a> {{-- Muestra vista de usuario --}}

                        {{-- Función 3.1: Descargar --}}
                        <a href="{{ route('diagrams.serve_file', $diagram->id) }}" class="btn btn-primary btn-sm" target="_blank">Descargar</a>

                        @if (!$diagram->trashed())
                            <form action="{{ route('admin.diagrams.destroy', $diagram->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE') {{-- Método DELETE para resource controller --}}
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar (soft delete) este elemento?')">Eliminar</button>
                            </form>
                        @else
                            <form action="{{ route('admin.diagrams.restore', $diagram->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Estás seguro de restaurar este elemento?')">Restaurar</button>
                            </form>
                            <form action="{{ route('admin.diagrams.force_delete', $diagram->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE') {{-- Método DELETE HTTP --}}
                                <button type="submit" class="btn btn-dark btn-sm" onclick="return confirm('¡ADVERTENCIA! ¿Estás seguro de eliminar PERMANENTEMENTE este elemento? Romperá referencias.')">Borrar Perm.</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No se encontraron diagramas o manuales.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $diagrams->appends(request()->query())->links() }}
    </div>
</div>
@endsection