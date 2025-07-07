@extends('layouts.app')
@section('content')
    <div class="container mt-5">
        <h3>Visualización en línea:</h3>
        <div class="embed-responsive embed-responsive-16by9">
            <iframe class="embed-responsive-item" height="600" width=100%
            src="{{ $sheetLink }}" allowfullscreen></iframe>
        <div class="alert alert-success" role="alert">
            El reporte "<strong>{{ $newSheetTitle }}</strong>" ha sido generado exitosamente en Google Sheets.
        </div>
        <p>Puedes verlo aquí:</p>
        <p><a href="{{ $sheetLink }}" target="_blank" class="btn btn-primary">Abrir Reporte en Google Sheets</a></p>
        <p>Recuerda que puedes compartirlo o descargarlo desde Google Sheets.</p>
    </div>
@endsection