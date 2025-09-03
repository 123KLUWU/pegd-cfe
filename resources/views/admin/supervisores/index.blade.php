@extends('layouts.app')

@section('title', 'Supervisores')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Supervisores</h1>
        <a href="{{ route('admin.supervisores.create') }}" class="btn btn-primary">+ Nuevo</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form class="row g-2 mb-3" method="GET">
        <div class="col-md-4">
            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por nombre o email">
        </div>
        <div class="col-md-3 form-check d-flex align-items-center">
            <input class="form-check-input me-2" type="checkbox" name="with_trashed" value="1" id="withTrashed"
                   @checked($incluyeEliminados)>
            <label class="form-check-label" for="withTrashed">Incluir eliminados</label>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100">Filtrar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($supervisores as $s)
                    <tr @if($s->deleted_at) class="table-warning" @endif>
                        <td>{{ $s->name }}</td>
                        <td>{{ $s->email }}</td>
                        <td class="text-end">
                            @if(!$s->deleted_at)
                                <a href="{{ route('admin.supervisores.edit', $s) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                <form action="{{ route('admin.supervisores.destroy', $s) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Enviar a papelera?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            @else
                                <form action="{{ route('admin.supervisores.restore', $s->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success">Restaurar</button>
                                </form>
                                <form action="{{ route('admin.supervisores.force-delete', $s->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Eliminar permanentemente?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Eliminar definitivo</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">Sin registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $supervisores->links() }}
</div>
@endsection
