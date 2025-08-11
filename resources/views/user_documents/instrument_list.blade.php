{{-- resources/views/user_documents/instrument_list.blade.php (Nivel 2: Lista de Instrumentos) --}}
@extends('layouts.app')

@section('title', 'Instrumentos de la Unidad: ' . $unidad->name)

@section('content')
<div class="container mt-5">
    <a href="{{ route('user.generated-documents.grouped.units') }}" class="btn btn-secondary mb-3">← Volver a Unidades</a>
    <h1>Instrumentos de la Unidad: {{ $unidad->name }}</h1>
    <p class="lead">Selecciona un instrumento para ver sus documentos generados.</p>

    @forelse ($instrumentos as $instrumento)
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">{{ $instrumento->tag }}</h5>
                    <p class="card-text text-muted mb-0">({{ $instrumento->generated_documents_count }} documentos)</p>
                </div>
                <a href="{{ route('user.generated-documents.grouped.documents', ['unidad' => $unidad->id, 'instrumento' => $instrumento->id]) }}" class="btn btn-primary">
                    Ver Documentos
                </a>
            </div>
        </div>
    @empty
        <div class="alert alert-info" role="alert">
            No se encontraron instrumentos con documentos generados en esta unidad.
        </div>
    @endforelse
</div>
@endsection