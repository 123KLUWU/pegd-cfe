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

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Google Drive ID</th>
                <th>Activa</th>
                <th>Creada Por</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($templates as $template)
                <tr>
                    <td>{{ $template->id }}</td>
                    <td>{{ $template->name }}</td>
                    <td>{{ ucfirst($template->type) }}</td>
                    <td><a href="https://docs.google.com/{{ $template->type }}s/d/{{ $template->google_drive_id }}/edit" target="_blank">{{ Str::limit($template->google_drive_id, 15) }}</a></td>
                    <td>
                        @if($template->is_active)
                            <span class="badge bg-success">Sí</span>
                        @else
                            <span class="badge bg-danger">No</span>
                        @endif
                    </td>
                    <td>{{ $template->createdBy->name ?? 'N/A' }}</td>
                    <td>
                        @if ($template->trashed())
                            <span class="badge bg-secondary">Eliminada</span>
                        @else
                            <span class="badge bg-primary">Activa</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.templates.edit', $template->id) }}" class="btn btn-warning btn-sm">Editar</a>
                        <a href="{{ route('admin.templates.prefilled-data.create', $template->id) }}" class="btn btn-info btn-sm">Gestionar Datos Prefill</a>
                        
                        @if (!$template->trashed())
                            <form action="{{ route('admin.templates.delete', $template->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar (soft delete) esta plantilla?')">Eliminar</button>
                            </form>
                        @else
                            <form action="{{ route('admin.templates.restore', $template->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Estás seguro de restaurar esta plantilla?')">Restaurar</button>
                            </form>
                            <form action="{{ route('admin.templates.force_delete', $template->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE') {{-- Método DELETE HTTP --}}
                                <button type="submit" class="btn btn-dark btn-sm" onclick="return confirm('¡ADVERTENCIA! ¿Estás seguro de eliminar PERMANENTEMENTE esta plantilla? Romperá referencias.')">Borrar Perm.</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No hay plantillas registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection