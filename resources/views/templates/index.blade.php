{{-- resources/views/templates/index.blade.php --}}
@extends('layouts.app') {{-- Asegúrate que este sea tu layout base principal --}}

@section('title', 'Menú de Plantillas')

@section('content')
<div class="container mt-5">
    <h1>Menú de Plantillas Disponibles</h1>

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
    <form method="GET" action="{{ route('templates.index') }}" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label">Buscar por Nombre/Descripción:</label>
                <input type="text" class="form-control" id="search" name="search" value="{{ $search_query }}" placeholder="Buscar...">
            </div>
            <div class="col-md-3">
                <label for="type" class="form-label">Filtrar por Tipo:</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Todos los Tipos</option>
                    <option value="docs" {{ $selected_type == 'docs' ? 'selected' : '' }}>Google Docs</option>
                    <option value="sheets" {{ $selected_type == 'sheets' ? 'selected' : '' }}>Google Sheets</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="category_id" class="form-label">Filtrar por Categoría:</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">Todas las Categorías</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ $selected_category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="row">
        @forelse ($templates as $template)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                                                {{-- Miniatura de la Plantilla --}}
                        <div class="template-thumbnail-container text-center my-3" style="width: 100%; height: 120px; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid #eee; border-radius: .25rem;">
                            @if($template->thumbnail_link)
                                {{-- Usar object-fit: cover; para que la imagen llene el espacio y se recorte --}}
                                {{-- ¡NUEVO!: object-position: top; para enfocar la parte superior --}}
                                <img src="{{ $template->thumbnail_link }}" alt="Miniatura de {{ $template->name }}" 
                                     class="img-fluid rounded" 
                                     style="width: 100%; height: 100%; object-fit: cover; object-position: top;">
                            @else
                                <div class="text-muted small">Miniatura no disponible</div>
                            @endif
                        </div>
                        <h5 class="card-title">{{ $template->name }}</h5>
                        <p class="card-text">{{ Str::limit($template->description, 80) ?? 'Sin descripción.' }}</p>
                        
                        <p class="card-text"><small class="text-muted">Tipo: {{ ucfirst($template->type) }}</small></p>
                        @if ($template->category)
                            <p class="card-text"><small class="text-muted">Categoría: {{ $template->category->name }}</small></p>
                        @endif
                        
                        <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center">
                            {{-- Botón Principal: Generar Documento --}}
                            <a href="{{ route('documents.customize.form', $template->id) }}" class="btn btn-primary btn-sm mb-2 me-2">Generar Documento</a>

                            {{-- Botón "3 Puntitos" (Dropdown de Opciones Secundarias) --}}
                            <div class="dropdown mb-2">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton_{{ $template->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Más Opciones</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                                        <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                    </svg>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton_{{ $template->id }}">
                                    {{-- Opción 1: Ver PDF --}}
                                    @if($template->pdf_file_path)
                                        <li><a class="dropdown-item" href="{{ route('templates.show_pdf_preview', $template->id) }}" target="_blank">Ver PDF</a></li>
                                    @endif
                                    {{-- Opción 2: Descargar XLSX/DOCX --}}
                                    @if($template->office_file_path)
                                        <li><a class="dropdown-item" href="{{ route('templates.download_office', $template->id) }}" target="_blank">Descargar {{ strtoupper($template->type) == 'DOCS' ? 'DOCX' : 'XLSX' }}</a></li>
                                    @endif
                                    {{-- Opción 3: Abrir Plantilla Original en Google Drive --}}
                                    <li><a class="dropdown-item" href="{{ route('templates.generate_qr_pdf', $template->id) }}" target="_blank">Imprimir QR en PDF</a></li>
                                    {{-- Aquí podrías añadir más opciones si las necesitas en el futuro --}}
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info" role="alert">
                    No se encontraron plantillas activas con los criterios de búsqueda/filtro.
                </div>
            </div>
        @endforelse
    </div>
    {{-- Paginación --}}
    <div class="d-flex justify-content-center">
       {{ $templates->appends(request()->query())->links() }}
    </div>
</div>
@endsection