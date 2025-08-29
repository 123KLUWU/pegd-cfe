<?php

namespace App\Http\Controllers;

use App\Mail\DocAdjuntoMail;
use App\Services\DriveExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\GeneratedDocument;

class MailDocController extends Controller
{
    public function __construct(private DriveExporter $exporter) {}

    // Muestra el formulario (con valores por defecto)
    public function form(GeneratedDocument $document)
    {
        $defaultSubject = 'Documento PEGD';
        $defaultMsg     = "Hola,\n\nTe comparto el documento adjunto.\n\nSaludos.";
        $docTitle       = $document->title;

        return view('emails.send_doc', compact(
            'document', 'defaultSubject', 'defaultMsg', 'docTitle'
        ));
    }

    // Envía el correo con el adjunto
    public function send(Request $request, GeneratedDocument $document)
    {
        $data = $request->validate([
            'destinatarios' => ['required'],
            'asunto'        => ['required', 'string', 'max:150'],
            'mensaje'       => ['nullable', 'string'],
        ]);
    
        $to = is_string($data['destinatarios'])
            ? array_filter(array_map('trim', explode(',', $data['destinatarios'])))
            : (array) $data['destinatarios'];
    
        // Exporta archivo con formato nativo (Word, Excel, etc.)
        [$tmpPath, $filename] = $this->exporter->downloadWithNativeFormat($document->google_drive_id);
    
        foreach ($to as $email) {
            Mail::to($email)->send(new DocAdjuntoMail(
                asunto:        $data['asunto'],
                mensajePlano:  $data['mensaje'] ?? "Te comparto el documento adjunto.",
                rutaAdjunto:   $tmpPath,
                nombreAdjunto: $filename
            ));
        }
    
        @unlink($tmpPath);
    
        return back()->with('status', 'Correo enviado con adjunto.');
    }
    
}
