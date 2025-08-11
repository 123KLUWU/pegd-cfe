{{-- resources/views/user_documents/unit_list.blade.php (Nivel 1: Lista de Unidades) --}}
@extends('layouts.app')

@section('title', 'Mis Documentos por Unidad')

@section('content')
<div class="container mt-5">
    <h1>Documentos Generados por Unidad</h1>
    <p class="lead">Selecciona una unidad para ver los instrumentos con documentos asociados.</p>

    @forelse ($unidades as $unidad)
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">{{ $unidad->unidad }}</h5>
                    <p class="card-text text-muted mb-0">({{ $unidad->generated_documents_count }} documentos)</p>
                </div>
                <a href="{{ route('user.generated-documents.grouped.instruments', $unidad->id) }}" class="btn btn-primary">
                    Ver Instrumentos
                </a>
            </div>
        </div>
    @empty
        <div class="alert alert-info" role="alert">
            No se encontraron unidades con documentos generados.
        </div>
    @endforelse
</div>
@endsection