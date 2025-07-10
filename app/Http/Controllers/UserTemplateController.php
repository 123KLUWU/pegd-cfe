<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template; // Importa el modelo de plantilla
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; // Para gestionar archivos
use Illuminate\Support\Facades\Log;

class UserTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Opcional: Si quieres que solo usuarios con un permiso específico puedan descargar/ver estas copias
        // $this->middleware('permission:download templates');
    }

     /**
     * Sirve el archivo PDF de previsualización de una plantilla.
     * Esta es la URL a la que apuntará el iframe o el enlace en las vistas de usuario/admin.
     *
     * @param Template $template
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function showPdfPreview(Template $template)
    {
        // Asegurarse de que el archivo PDF existe en el storage
        if (!$template->pdf_file_path || !Storage::disk('public')->exists($template->pdf_file_path)) {
            Log::error("PDF de previsualización no encontrado para plantilla ID: {$template->id}, Path: {$template->pdf_file_path}");
            abort(404, 'PDF de previsualización no encontrado.');
        }

        // Obtiene la ruta física completa del archivo
        $path = Storage::disk('public')->path($template->pdf_file_path);

        // Retorna el archivo al navegador con las cabeceras correctas (application/pdf)
        return response()->file($path);
    }
    
     /**
     * Descarga la copia del archivo de la plantilla en formato Office (DOCX/XLSX).
     *
     * @param Template $template
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadOfficeFile(Template $template)
    {
        // Asegurarse de que el archivo Office existe en el storage
        if (!$template->office_file_path || !Storage::disk('public')->exists($template->office_file_path)) {
            Log::error("Copia Office no encontrada para plantilla ID: {$template->id}, Path: {$template->office_file_path}");
            abort(404, 'Copia de plantilla en formato Office no encontrada.');
        }

        // Obtiene la ruta física completa del archivo
        $path = Storage::disk('public')->path($template->office_file_path);

        // Genera un nombre de archivo para la descarga
        $fileName = $template->name . '.' . ($template->type === 'docs' ? 'docx' : 'xlsx');

        // Retorna el archivo para descarga forzada
        return response()->download($path, $fileName);
    }
}
