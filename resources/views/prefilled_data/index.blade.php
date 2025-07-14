{{-- resources/views/prefilled_data/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Formatos Predeterminados')

@section('content')
<div class="container mt-5">
    <h1>Formatos Predeterminados Disponibles</h1>

    @if (session('status'))
        <div class="alert alert-success mt-3" role="alert">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mt-3" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- Formulario de Búsqueda y Filtros --}}
    <form method="GET" action="{{ route('prefilled-data.index') }}" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label">Buscar por Nombre/Descripción:</label>
                <input type="text" class="form-control" id="search" name="search" value="{{ $search_query }}" placeholder="Buscar...">
            </div>
            <div class="col-md-3">
                <label for="template_id" class="form-label">Filtrar por Plantilla:</label>
                <select class="form-select" id="template_id" name="template_id">
                    <option value="">Todas las Plantillas</option>
                    @foreach ($available_templates as $template)
                        <option value="{{ $template->id }}" {{ $selected_template_id == $template->id ? 'selected' : '' }}>
                            {{ $template->name }} ({{ ucfirst($template->type) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="row">
        @forelse ($prefilledData as $data)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $data->name }}</h5>
                        <p class="card-text"><small class="text-muted">Plantilla: {{ $data->template->name ?? 'N/A' }} ({{ ucfirst($data->template->type ?? '') }})</small></p>
                        <p class="card-text">{{ Str::limit($data->description, 80) ?? 'Sin descripción.' }}</p>
                        
                        <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center">
                            {{-- Botón para Generar Documento con este formato prellenado --}}
                            <form action="{{ route('documents.generate.predefined') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="prefilled_data_id" value="{{ $data->id }}">
                                <button type="submit" class="btn btn-success btn-sm mb-2 me-2">Generar Documento</button>
                            </form>

                            {{-- Opcional: Ver detalles del JSON (solo para depuración o admin) --}}
                            {{-- <button type="button" class="btn btn-sm btn-outline-secondary mb-2" data-bs-toggle="modal" data-bs-target="#dataJsonModal" data-json="{{ json_encode($data->data_json) }}">Ver Datos</button> --}}
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info" role="alert">
                    No se encontraron formatos predeterminados disponibles con los criterios de búsqueda/filtro.
                </div>
            </div>
        @endforelse
    </div>

    {{-- Paginación --}}
    <div class="d-flex justify-content-center">
        {{ $prefilledData->appends(request()->query())->links() }}
    </div>
</div>
@endsection