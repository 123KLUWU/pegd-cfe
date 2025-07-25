{{-- resources/views/prefilled_data/qr_preview.blade.php --}}
@extends('layouts.app')

@section('title', 'Previsualización QR: ' . $prefilledData->name)

@section('content')
<div class="container mt-5">
    <a href="{{ route('prefilled-data.index') }}" class="btn btn-secondary mb-3">← Volver a Formatos Predeterminados</a>

    <h1>Previsualización de QR para Formato: {{ $prefilledData->name }}</h1>
    <p class="lead">Plantilla: {{ $prefilledData->template->name ?? 'N/A' }} ({{ ucfirst($prefilledData->template->type ?? '') }})</p>
    <p>{{ $prefilledData->description ?? 'Sin descripción.' }}</p>

    <div class="alert alert-info" role="alert">
        Escanea este código QR con tu dispositivo móvil. Te redirigirá a la aplicación, solicitará inicio de sesión si no estás autenticado, y luego generará el documento automáticamente abriéndolo en una nueva pestaña.
    </div>

    <div class="row text-center">

            {{-- Botón para Generar Documento (Ahora redirige directamente) --}}
            <a href="{{ route('documents.generate.predefined_by_qr', ['prefilled_data_id' => $prefilledData->id]) }}" target="_blank" class="btn btn-success btn-lg mb-3">Generar Documento Ahora</a>
            
            {{-- Botón para Imprimir el QR en PDF --}}
            <a href="{{ route('prefilled-data.generate_qr_pdf', $prefilledData->id) }}" target="_blank" class="btn btn-outline-primary mb-3">Imprimir QR en PDF</a>
        </div>
    </div>
</div>
@endsection
