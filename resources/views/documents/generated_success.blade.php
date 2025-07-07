{{-- resources/views/documents/generated_success.blade.php --}}
@extends('layouts.app')

@section('title', 'Documento Generado Exitosamente')

@section('content')
<div class="container mt-5">
    <div class="alert alert-success" role="alert">
        ¡Tu documento ha sido generado exitosamente!
    </div>

    @if (session('docLink'))
        <h3 class="mb-3">Documento: <strong>{{ session('docTitle') ?? 'Sin Título' }}</strong></h3>
        <p>Puedes verlo y editarlo en Google Drive aquí:</p>
        <p>
            <a href="{{ session('docLink') }}" target="_blank" class="btn btn-primary btn-lg">Abrir Documento en Google Drive</a>
        </p>
        <p class="mt-3">
            Recuerda que este documento puede tener una visibilidad temporal o restringida según la configuración.
        </p>
    @else
        <div class="alert alert-warning" role="alert">
            No se pudo obtener el enlace directo al documento generado. Por favor, revisa tus documentos en Google Drive.
        </div>
    @endif

    <p class="mt-4">
        <a href="{{ route('templates.index') }}" class="btn btn-secondary">Generar otro documento</a>
        <a href="{{ route('templates.index') }}" class="btn btn-info">Ir al Dashboard</a>
    </p>
</div>
@endsection