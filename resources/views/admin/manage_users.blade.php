@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Usuarios Pendientes de Aprobación</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>RPE</th>
                <th>Email</th>
                <th>Estado de Solicitud</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($allUsers as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->rpe }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        {{-- Formulario para Aprobar --}}
                        <form action="{{ route('admin.users.approve', $user) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">Aprobar</button>
                        </form>

                        {{-- Formulario para Rechazar --}}
                        <form action="{{ route('admin.users.reject', $user) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm">Rechazar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No hay solicitudes de usuario pendientes.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection