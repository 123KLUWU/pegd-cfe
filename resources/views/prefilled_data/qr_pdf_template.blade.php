{{-- resources/views/prefilled_data/qr_pdf_template.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>QR Formato {{ $formatName }}</title>
    <meta charset="utf-8">
    <style>
        /* CSS específico para el PDF */
        body { font-family: sans-serif; margin: 20mm; }
        .container-pdf { width: 100%; text-align: center; }
        h1 { color: #333; font-size: 24px; margin-bottom: 20px; }
        h2 { color: #555; font-size: 18px; margin-bottom: 10px; }
        p { font-size: 14px; color: #555; }
        .qr-code { margin: 30px auto; border: 1px solid #ccc; padding: 10px; width: 220px; height: 220px; }
        .qr-code img { width: 100%; height: 100%; display: block; } /* La imagen base64 se ajustará */
        .url-text { font-size: 10px; word-break: break-all; }
        .footer { margin-top: 50px; font-size: 12px; color: #888; }
    </style>
</head>
<body>
    <div class="container-pdf">
        <h1>Formato: {{ $formatName }}</h1>
        <h2>Plantilla: {{ $templateName }}</h2>
        <p>{{ $formatDescription }}</p>

        <div class="qr-code">
            <img src="data:image/svg+xml;base64,{!! $qrCodeBase64 !!}" alt="Código QR">
        </div>
        <p class="url-text">{{ $qrContentUrl }}</p>

        <div class="footer">
            <p>Generado el {{ $generatedAt }}</p>
            <p>Escanea para generar un documento prellenado.</p>
        </div>
    </div>
</body>
</html>
