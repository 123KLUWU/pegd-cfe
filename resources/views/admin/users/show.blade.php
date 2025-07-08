{{-- resources/views/admin/users/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detalles de Usuario: ' . $user->name)

@section('content')
<div class="container mt-5">
    <a href="{{ route('users.index') }}" class="btn btn-secondary mb-3">← Volver a Usuarios</a>

    <h1>Detalles de Usuario: {{ $user->name }} (RPE: {{ $user->rpe }})</h1>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    Información del Usuario
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Nombre:</strong> {{ $user->name }}</li>
                    <li class="list-group-item"><strong>RPE:</strong> {{ $user->rpe }}</li>
                    <li class="list-group-item"><strong>Email:</strong> {{ $user->email ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Estado:</strong> {{ ucfirst($user->status) }}</li>
                    <li class="list-group-item"><strong>Fecha de Registro:</strong> {{ $user->created_at->format('d/m/Y H:i') }}</li>
                    <li class="list-group-item"><strong>Último Login:</strong> {{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Nunca' }}</li>
                    <li class="list-group-item"><strong>IP Último Login:</strong> {{ $user->last_login_ip ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Roles:</strong>
                        @forelse ($user->getRoleNames() as $roleName)
                            <span class="badge bg-info">{{ $roleName }}</span>
                        @empty
                            <span class="badge bg-light text-dark">Ninguno</span>
                        @endforelse
                    </li>
                </ul>
            </div>
            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning me-2">Editar Usuario</a>
            @if (!$user->trashed())
                <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar lógicamente este usuario?')">Eliminar (Soft)</button>
                </form>
            @else
                <form action="{{ route('admin.users.restore', $user->id) }}" method="POST" class="d-inline me-2">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('¿Estás seguro de restaurar este usuario?')">Restaurar</button>
                </form>
                <form action="{{ route('admin.users.force_delete', $user->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-dark" onclick="return confirm('¡PELIGRO! ¿Estás seguro de eliminar PERMANENTEMENTE este usuario? Esta acción es irreversible.')">Borrar Perm.</button>
                </form>
            @endif
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    Actividad Reciente del Usuario
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($userActivities as $activity)
                        <li class="list-group-item">
                            <small class="text-muted">{{ $activity->created_at->format('d/m/Y H:i') }}</small><br>
                            <strong>{{ $activity->event }}:</strong> {{ $activity->description }}
                            @if($activity->properties->isNotEmpty())
                                <pre class="small text-muted mt-1">{{ json_encode($activity->properties->toArray(), JSON_PRETTY_PRINT) }}</pre>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item">No hay actividad reciente para este usuario.</li>
                    @endforelse
                </ul>
                <div class="card-footer d-flex justify-content-center">
                    {{ $userActivities->links('pagination::bootstrap-5', ['pageName' => 'activities_page']) }}
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    Documentos Generados por el Usuario
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($userGeneratedDocuments as $doc)
                        <li class="list-group-item">
                            <small class="text-muted">{{ $doc->created_at->format('d/m/Y H:i') }}</small><br>
                            <a href="https://docs.google.com/{{ $doc->type }}s/d/{{ $doc->google_drive_id }}/edit" target="_blank">{{ $doc->title }}</a>
                            ({{ ucfirst($doc->type) }}, {{ ucfirst(str_replace('_', ' ', $doc->visibility_status)) }})
                        </li>
                    @empty
                        <li class="list-group-item">Este usuario no ha generado documentos.</li>
                    @endforelse
                </ul>
                <div class="card-footer d-flex justify-content-center">
                    {{ $userGeneratedDocuments->links('pagination::bootstrap-5', ['pageName' => 'documents_page']) }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection