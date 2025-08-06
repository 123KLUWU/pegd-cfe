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
                                                {{-- Miniatura de la Plantilla Asociada --}}
                                                <div class="template-thumbnail-container text-center my-3" style="width: 100%; height: 120px; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid #eee; border-radius: .25rem;">
                                                    @if($data->template && $data->template->thumbnail_link)
                                                        <img src="{{ $data->template->thumbnail_link }}" alt="Miniatura de {{ $data->template->name }}" 
                                                             class="img-fluid rounded" 
                                                             style="width: 100%; height: 100%; object-fit: cover; object-position: top;">
                                                    @else
                                                        <div class="text-muted small">Miniatura no disponible</div>
                                                    @endif
                                                </div>
                        <h5 class="card-title">{{ $data->name }}</h5>
                        <p class="card-text"><small class="text-muted">Plantilla: {{ $data->template->name ?? 'N/A' }} ({{ ucfirst($data->template->type ?? '') }})</small></p>
                        <p class="card-text">{{ Str::limit($data->description, 80) ?? 'Sin descripción.' }}</p>

                        <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center">
                              {{-- Botón Principal: Generar Documento (APUNTA A LA PÁGINA DE CONFIRMACIÓN) --}}
                              <a href="{{ route('prefilled-data.generate_confirmation_form', $data->id) }}" class="btn btn-success btn-sm mb-2 me-2 flex-grow-1">Generar Documento</a>

                            {{-- Botón para Generar Documento con este formato prellenado 
                            <form action="{{ route('documents.generate.predefined') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="prefilled_data_id" value="{{ $data->id }}">
                                <button type="submit" class="btn btn-success btn-sm mb-2 me-2" target="_blank" >Generar Documento</button>
                            </form>
                            --}}
                                                        {{-- Botón "3 Puntitos" (Dropdown de Opciones Secundarias) --}}
                            <div class="dropdown mb-2">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton_{{ $template->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Más Opciones</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                                        <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                    </svg>
                                </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton_{{ $data->id }}">
                                {{-- Opción 1: Abrir Plantilla Original en Google Drive --}}
                                <li><a class="dropdown-item" href="{{ route('predefined.generate_qr_pdf', $data->id) }}" target="_blank">Imprimir QR en PDF</a></li>
                                {{-- Aquí podrías añadir más opciones si las necesitas en el futuro --}}
                            </ul>
                        </div>
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