{{-- resources/views/diagrams/qr_pdf_template.blade.php 
https://es.stackoverflow.com/questions/309482/integraci%c3%b3n-laravel-dompdf-y-qrcode-simplesoftwareio
--}}
<!DOCTYPE html>
<html>
<head>
    <title>QR {{ $diagramName }}</title>
    <meta charset="utf-8">
    <style>
        /* CSS específico para el PDF */
        body { font-family: sans-serif; margin: 20mm; }
        .container-pdf { width: 100%; text-align: center; }
        h1 { color: #333; font-size: 24px; margin-bottom: 20px; }
        p { font-size: 14px; color: #555; }
        .qr-code { margin: 30px auto; border: 1px solid #ccc; padding: 10px; width: 220px; height: 220px; }
        .qr-code svg { width: 100%; height: 100%; display: block; } /* SVG para alta calidad */
        .url-text { font-size: 10px; word-break: break-all; }
        .footer { margin-top: 50px; font-size: 12px; color: #888; }
    </style>
</head>
<body>
    <div class="container-pdf">
        <h1>{{ $diagramName }}</h1>
        <p>{{ $diagramDescription }}</p>
        @if($machineCategory)
            <p>Máquina: {{ $machineCategory }}</p>
        @endif
        <img src="data:image/svg+xml;base64,{{ base64_encode($qrCodeSvg) }}">
            
        <p class="url-text">{{ $qrContentUrl }}</p>

        <div class="footer">
            <p>Generado por PEGD-CFE el {{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <p>Usuario: pdf01</p>
        <p>Contraseña: ctpalm2113</p>
    </div>
</body>
</html>