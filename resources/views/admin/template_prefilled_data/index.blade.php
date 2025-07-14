{{-- resources/views/admin/prefilled_data/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestión de Formatos Predeterminados (Admin)')

@section('content')
<div class="container mt-5">
    <h1>Gestión de Formatos Predeterminados</h1>

    {{-- Botón para crear nuevo prellenado 
    <a href="{{ route('admin.templates.prefilled-data.create') }}" class="btn btn-success mb-3">Crear Nuevo Formato Predeterminado</a>
    --}}
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
    <form method="GET" action="{{ route('admin.templates.prefilled-data.index') }}" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label">Buscar por Nombre/Descripción:</label>
                <input type="text" class="form-control" id="search" name="search" value="{{ $search_query }}" placeholder="Buscar...">
            </div>
            <div class="col-md-3">
                <label for="template_id" class="form-label">Filtrar por Plantilla:</label>
                <select class="form-select" id="template_id" name="template_id">
                    <option value="">Todas las Plantillas</option>
                    @foreach ($templates as $template)
                        <option value="{{ $template->id }}" {{ $selected_template_id == $template->id ? 'selected' : '' }}>
                            {{ $template->name }} ({{ ucfirst($template->type) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Estado:</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Activos</option>
                    <option value="trashed" {{ $selected_status == 'trashed' ? 'selected' : '' }}>Eliminados (Soft)</option>
                    <option value="with_trashed" {{ $selected_status == 'with_trashed' ? 'selected' : '' }}>Todos (act. + elim.)</option>
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
                <th>Plantilla</th>
                <th>Descripción</th>
                <th>Estado DB</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($prefilledData as $data)
                <tr>
                    <td>{{ $data->id }}</td>
                    <td>{{ $data->name }}</td>
                    <td>{{ $data->template->name ?? 'N/A' }}</td>
                    <td>{{ Str::limit($data->description, 80) ?? 'N/A' }}</td>
                    <td>
                        @if ($data->trashed())
                            <span class="badge bg-secondary">Eliminado</span>
                        @else
                            <span class="badge bg-primary">Normal</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.templates.prefilled-data.edit', $data->id) }}" class="btn btn-warning btn-sm">Editar</a>
                        
                        {{--
                        @if (!$data->trashed())
                            <form action="{{ route('admin.templates.prefilled-data.destroy', $data->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar (soft delete) este formato?')">Eliminar</button>
                            </form>
                        @else
                            <form action="{{ route('admin.templates.prefilled-data.restore', $data->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Estás seguro de restaurar este formato?')">Restaurar</button>
                            </form>
                            <form action="{{ route('admin.templates.prefilled-data.force_delete', $data->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-dark btn-sm" onclick="return confirm('¡ADVERTENCIA! ¿Estás seguro de eliminar PERMANENTEMENTE este formato? Esta acción es irreversible.')">Borrar Perm.</button>
                            </form>
                        @endif
                        --}}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No se encontraron formatos predeterminados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $prefilledData->appends(request()->query())->links() }}
    </div>
</div>
@endsection