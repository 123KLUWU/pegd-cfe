{{-- resources/views/diagrams/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Diagramas y Manuales')

@section('content')
<div class="container mt-5">
    <h1>Diagramas y Manuales</h1>

    {{-- Formulario de Búsqueda y Filtros --}}
    <form method="GET" action="{{ route('diagrams.index') }}" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label">Buscar por Nombre/Descripción:</label>
                <input type="text" class="form-control" id="search" name="search" value="{{ $search_query }}" placeholder="Buscar...">
            </div>
            <div class="col-md-3">
                <label for="type" class="form-label">Filtrar por Tipo:</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Todos los Tipos</option>
                    <option value="diagram" {{ $selected_type == 'diagram' ? 'selected' : '' }}>Diagramas</option>
                    <option value="manual" {{ $selected_type == 'manual' ? 'selected' : '' }}>Manuales</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="category" class="form-label">Filtrar por Máquina:</label>
                <select class="form-select" id="category" name="category">
                    <option value="">Todas las Máquinas</option>
                    @foreach ($available_categories as $category)
                        <option value="{{ $category }}" {{ $selected_category == $category ? 'selected' : '' }}>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="row">
        @forelse ($diagrams as $diagram)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $diagram->name }}</h5>
                        <p class="card-text"><small class="text-muted">Tipo: {{ ucfirst($diagram->type) }}</small></p>
                        @if($diagram->machine_category)
                            <p class="card-text"><small class="text-muted">Máquina: {{ $diagram->machine_category }}</small></p>
                        @endif
                        <p class="card-text">{{ Str::limit($diagram->description, 100) }}</p> {{-- Muestra una descripción limitada --}}

                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            {{-- Botón "Abrir/Ver" --}}
                            <a href="{{ route('diagrams.show', $diagram->id) }}" class="btn btn-primary btn-sm">Ver Diagrama</a>

                            {{-- Botón "Generar QR" (Opciones adicionales) --}}
                            <a href="{{ route('diagrams.generate_qr_pdf', $diagram->id) }}" class="btn btn-outline-secondary btn-sm">Generar QR</a>
                            {{-- Podrías usar un dropdown aquí si hay más opciones --}}
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info" role="alert">
                    No se encontraron diagramas o manuales con los criterios de búsqueda/filtro.
                </div>
            </div>
        @endforelse
    </div>

    {{-- Paginación --}}
    <div class="d-flex justify-content-center">
        {{ $diagrams->appends(request()->query())->links() }}
    </div>
</div>
@endsection