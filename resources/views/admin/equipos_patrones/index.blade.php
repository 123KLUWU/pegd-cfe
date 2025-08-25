{{-- resources/views/admin/equipos_patrones/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestión de Equipos Patrones (Admin)')

@section('content')
<div class="container mt-5">
    <h1>Gestión de Equipos Patrones</h1>

    <a href="{{ route('admin.equipos-patrones.create') }}" class="btn btn-success mb-3">Registrar Nuevo Equipo Patrón</a>

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
    <form method="GET" action="{{ route('admin.equipos-patrones.index') }}" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label">Buscar:</label>
                <input type="text" class="form-control" id="search" name="search" value="{{ $search_query }}" placeholder="Identificador, descripción, serie...">
            </div>
            <div class="col-md-3">
                <label for="estado" class="form-label">Filtrar por Estado:</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos los Estados</option>
                    <option value="CUMPLE" {{ $selected_estado == 'CUMPLE' ? 'selected' : '' }}>CUMPLE</option>
                    <option value="NO CUMPLE" {{ $selected_estado == 'NO CUMPLE' ? 'selected' : '' }}>NO CUMPLE</option>
                    <option value="CUMPLE PARCIALMENTE" {{ $selected_estado == 'CUMPLE PARCIALMENTE' ? 'selected' : '' }}>CUMPLE PARCIALMENTE</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Estado DB:</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Activos</option>
                    <option value="trashed" {{ $selected_status == 'trashed' ? 'selected' : '' }}>Eliminados (Soft)</option>
                    <option value="with_trashed" {{ $selected_status == 'with_trashed' ? 'selected' : '' }}>Todos (act. + elim.)</option>
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
                <th>Identificador</th>
                <th>Descripción</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>No. Serie</th>
                <th>Última Cal.</th>
                <th>Próxima Cal.</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($equipos as $equipo)
                <tr>
                    <td>{{ $equipo->id }}</td>
                    <td>{{ $equipo->identificador }}</td>
                    <td>{{ Str::limit($equipo->descripcion, 50) ?? 'N/A' }}</td>
                    <td>{{ $equipo->marca ?? 'N/A' }}</td>
                    <td>{{ $equipo->modelo ?? 'N/A' }}</td>
                    <td>{{ $equipo->numero_serie ?? 'N/A' }}</td>
                    <td>{{ $equipo->ultima_calibracion ? $equipo->ultima_calibracion->format('d/m/Y') : 'N/A' }}</td>
                    <td>{{ $equipo->proxima_calibracion ? $equipo->proxima_calibracion->format('d/m/Y') : 'N/A' }}</td>
                    <td>
                        <span class="badge bg-{{
                            $equipo->estado == 'CUMPLE' ? 'success' : (
                            $equipo->estado == 'CUMPLE PARCIALMENTE' ? 'warning text-dark' : 'danger')
                        }}">
                            {{ $equipo->estado }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.equipos-patrones.edit', $equipo->id) }}" class="btn btn-warning btn-sm">Editar</a>
                        
                        @if (!$equipo->trashed())
                            <form action="{{ route('admin.equipos-patrones.destroy', $equipo->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar (soft delete) este equipo patrón?')">Eliminar {{$equipo->id}}</button>
                            </form>
                        @else
                            <form action="{{ route('admin.equipos-patrones.restore', $equipo->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Estás seguro de restaurar este equipo patrón?')">Restaurar</button>
                            </form>
                            <form action="{{ route('admin.equipos-patrones.force_delete', $equipo->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-dark btn-sm" onclick="return confirm('¡ADVERTENCIA! ¿Estás seguro de eliminar PERMANENTEMENTE este equipo patrón? Esta acción es irreversible.')">Borrar Perm.</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">No se encontraron equipos patrones.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $equipos->appends(request()->query())->links() }}
    </div>
</div>
@endsection
