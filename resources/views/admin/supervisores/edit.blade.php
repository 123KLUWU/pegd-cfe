@extends('layouts.app')

@section('title', 'Editar Supervisor')

@section('content')
<div class="container mt-4">
    <h1 class="h3 mb-3">Editar Supervisor</h1>

    <form method="POST" action="{{ route('admin.supervisores.update', $supervisor) }}" class="card card-body">
        @csrf @method('PUT')

        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="name" value="{{ old('name', $supervisor->name) }}" class="form-control" required>
            @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Correo</label>
            <input type="email" name="email" value="{{ old('email', $supervisor->email) }}" class="form-control" required>
            @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.supervisores.index') }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-primary">Actualizar</button>
        </div>
    </form>
</div>
@endsection
