{{-- resources/views/admin/users/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestión de Usuarios (Admin)')

@section('content')
<div class="container mt-5">
    <h1>Gestión de Usuarios</h1>
    {{--
    <a href="{{ route('admin.users.create') }}" class="btn btn-success mb-3">Crear Nuevo Usuario</a> 
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
    <form method="GET" action="{{ route('users.index') }}" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label">Buscar por Nombre/RPE/Email:</label>
                <input type="text" class="form-control" id="search" name="search" value="{{ $search_query }}" placeholder="Buscar...">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Filtrar por Estado:</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Activos + Pendientes</option> {{-- Default filtering handled in controller --}}
                    <option value="Pendiente" {{ $selected_status == 'Pendiente' ? 'selected' : '' }}>Pendientes</option>
                    <option value="Activo" {{ $selected_status == 'Activo' ? 'selected' : '' }}>Activos</option>
                    <option value="Rechazado" {{ $selected_status == 'Rechazadooo' ? 'selected' : '' }}>Rechazados</option>
                    <option value="inactive" {{ $selected_status == 'inactive' ? 'selected' : '' }}>Inactivos</option>
                    <option value="trashed" {{ $selected_status == 'trashed' ? 'selected' : '' }}>Eliminados (Soft)</option>
                    <option value="with_trashed" {{ $selected_status == 'with_trashed' ? 'selected' : '' }}>Todos (incl. eliminados)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="role" class="form-label">Filtrar por Rol:</label>
                <select class="form-select" id="role" name="role">
                    <option value="">Todos los Roles</option>
                    @foreach ($available_roles as $role)
                        <option value="{{ $role->name }}" {{ $selected_role == $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
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
                <th>RPE</th>
                <th>Email</th>
                <th>Estado</th>
                <th>Rol(es)</th>
                <th>Último Login</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->rpe }}</td>
                    <td>{{ $user->email ?? 'N/A' }}</td>
                    <td>
                        <span class="badge bg-{{
                            $user->status == 'Activo' ? 'success' : (
                            $user->status == 'Pendiente' ? 'warning' : (
                            $user->status == 'Rechazado' || $user->status == 'inactive' ? 'danger' : 'secondary'))
                        }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </td>
                    <td>
                        @forelse ($user->getRoleNames() as $roleName)
                            <span class="badge bg-info">{{ $roleName }}</span>
                        @empty
                            <span class="badge bg-light text-dark">Ninguno</span>
                        @endforelse
                    </td>
                    <td>{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Nunca' }}</td>
                    <td>
                        {{-- Botones de Acción --}}
                        <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-outline-info">Ver</a>
                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-warning">Editar</a>

                        @if($user->status == 'Pendiente')
                            {{-- Botones de Aprobar/Rechazar si el usuario está pendiente --}}
                            {{-- Asegúrate de tener estas rutas en el AdminController si no están en UserController --}}
                            <form action="{{ route('admin.users.approve', $user->id) }}" method="POST" class="d-inline"> @csrf <button type="submit" class="btn btn-sm btn-success">Aprobar</button> </form>
                            <form action="{{ route('admin.users.reject', $user->id) }}" method="POST" class="d-inline"> @csrf <button type="submit" class="btn btn-sm btn-danger">Rechazar</button> </form>
                        @endif

                        @if (!$user->trashed())
                            {{-- Si no está soft-deleted, mostrar botón de eliminar (soft delete) --}}
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar lógicamente este usuario?')">Eliminar</button>
                            </form>
                        @else
                            {{-- Si está soft-deleted, mostrar botón de restaurar y borrar permanente --}}
                            <form action="{{ route('admin.users.restore', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('¿Estás seguro de restaurar este usuario?')">Restaurar</button>
                            </form>
                            <form action="{{ route('admin.users.force_delete', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-dark" onclick="return confirm('¡PELIGRO! ¿Estás seguro de eliminar PERMANENTEMENTE este usuario? Esta acción es irreversible.')">Borrar Perm.</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No se encontraron usuarios.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $users->appends(request()->query())->links() }}
    </div>
</div>
@endsection
