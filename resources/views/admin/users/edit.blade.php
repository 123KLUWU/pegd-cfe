{{-- resources/views/admin/users/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Editar Usuario: ' . $user->name)

@section('content')
<div class="container mt-5">
    <h1>Editar Usuario: {{ $user->name }} (RPE: {{ $user->rpe }})</h1>

    <form method="POST" action="{{ route('users.update', $user->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="name" class="form-label">Nombre:</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required autofocus>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="rpe" class="form-label">RPE:</label>
            <input type="text" class="form-control @error('rpe') is-invalid @enderror" id="rpe" name="rpe" value="{{ old('rpe', $user->rpe) }}" required>
            @error('rpe')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email (Opcional):</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Nueva Contraseña (Dejar vacío para no cambiar):</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirmar Nueva Contraseña:</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Estado:</label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                <option value="Pendiente" {{ old('status', $user->status) == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="Activo" {{ old('status', $user->status) == 'Activo' ? 'selected' : '' }}>Activo</option>
                <option value="Rechazado" {{ old('status', $user->status) == 'Rechazado' ? 'selected' : '' }}>Rechazado</option>
                <option value="inactive" {{ old('status', $user->status) == 'inactive' ? 'selected' : '' }}>Inactivo</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="roles" class="form-label">Roles:</label>
            <select class="form-select @error('roles') is-invalid @enderror" id="roles" name="roles[]" multiple>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ in_array($role->name, old('roles', $userRoles->toArray())) ? 'selected' : '' }}>{{ $role->name }}</option>
                @endforeach
            </select>
            @error('roles')<div class="invalid-invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
