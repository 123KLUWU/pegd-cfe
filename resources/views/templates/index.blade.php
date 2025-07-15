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
                        <h5 class="card-title">{{ $template->name }}</h5>
                        <p class="card-text">{{ Str::limit($template->description, 80) ?? 'Sin descripción.' }}</p>
                        <p class="card-text"><small class="text-muted">Tipo: {{ ucfirst($template->type) }}</small></p>
                        @if ($template->category)
                            <p class="card-text"><small class="text-muted">Categoría: {{ $template->category->name }}</small></p>
                        @endif

                        <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center">
                            
                            {{-- Botón Consolidado: "Generar Documento" --}}
                            <form action="{{ route('documents.generate.blank') }}" method="POST">
                                @csrf
                                <input type="hidden" name="template_id" value="{{ $template->id }}">
                                <button type="submit" class="btn btn-primary btn-sm mb-2 me-2">Generar Documento</button>
                            </form>

                            {{-- Botones de Descarga Directa (para usuario) --}}
                            @if($template->pdf_file_path)
                                <a href="{{ route('templates.show_pdf_preview', $template->id) }}" target="_blank" class="btn btn-outline-info btn-sm mb-2 me-2">Ver PDF</a>
                            @endif
                            @if($template->office_file_path)
                                <a href="{{ route('templates.download_office', $template->id) }}" class="btn btn-outline-secondary btn-sm mb-2">Descargar {{ strtoupper($template->type) == 'DOCS' ? 'DOCX' : 'XLSX' }}</a>
                            @endif
                            <p class="text-center mt-3">
                                <a href="{{ route('templates.generate_qr_pdf', $template->id) }}" class="btn btn-outline-secondary btn-sm mb-2" target="_blank">Imprimir QR en PDF</a>
                            </p>
                            {{-- Nota: Opciones de Admin ya no van aquí --}}
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