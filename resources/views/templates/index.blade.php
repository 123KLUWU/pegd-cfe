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

    <div class="row">
        @forelse ($templates as $template)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $template->name }}</h5>
                        <p class="card-text">{{ $template->description ?? 'Sin descripción.' }}</p>
                        <p class="card-text"><small class="text-muted">Tipo: {{ ucfirst($template->type) }}</small></p>

                        <div class="mt-3">
                            {{-- OPCIÓN 1: Generar en Blanco --}}
                            <form action="{{ route('documents.generate.blank') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="template_id" value="{{ $template->id }}">
                                <button type="submit" class="btn btn-primary btn-sm mb-2">Generar en Blanco</button>
                            </form>

                            {{-- OPCIÓN 2: Generar Predeterminado --}}
                            {{-- Si tienes múltiples formatos predefinidos por plantilla, aquí tendrías un <select> --}}
                            <form action="{{ route('documents.generate.predefined') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="template_id" value="{{ $template->id }}">
                                {{-- Si necesitas pasar un ID específico de formato predefinido: --}}
                                {{-- <input type="hidden" name="predefined_format_id" value="ID_AQUI"> --}}
                                <button type="submit" class="btn btn-info btn-sm mb-2">Generar Predeterminado</button>
                            </form>

                            {{-- OPCIÓN 3: Personalizar (Lleva a un formulario de personalización) --}}
                            <a href="{{ route('documents.customize.form', $template->id) }}" class="btn btn-secondary btn-sm mb-2">Personalizar</a>

                            {{-- Opciones de Administrador (ejemplo) --}}
                            @can('manage templates')
                                <a href="{{ route('admin.templates.edit', $template->id) }}" class="btn btn-warning btn-sm mb-2">Editar Plantilla (Admin)</a>
                                
                                <form action="#" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm mb-2" onclick="return confirm('¿Estás seguro de eliminar esta plantilla?')">Eliminar (Admin)</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <p>No hay plantillas activas disponibles. ¡Crea algunas en el panel de administración!</p>
            </div>
        @endforelse
    </div>
</div>
@endsection