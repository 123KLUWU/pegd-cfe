<?php

namespace App\Http\Controllers;

use App\Models\Diagram; // Tu modelo Diagram
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Para servir archivos
use SimpleSoftwareIO\QrCode\Facades\QrCode; // Para generar QR
use Barryvdh\DomPDF\Facade\Pdf; // ¡Importa la fachada de DomPDF!
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DiagramController extends Controller
{
    /**
     * Constructor del controlador.
     * Protege las rutas para usuarios autenticados y con permiso de ver diagramas.
     */
    public function __construct()
    {
        $this->middleware('auth');
        // Asumiendo que tienes un permiso 'view diagrams' en Spatie
        $this->middleware('permission:view diagrams');
    }

    /**
     * Muestra el listado de diagramas/manuales con opciones de búsqueda y filtro.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Diagram::where('is_active', true);

        /**
         * logica para la busqueda
         * se obtiene el input del search
         * se buscan coincidencias en los parametros
         * de los diagramas (nombre, descripcion, categoria de la maquina)
         */
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('machine_category', 'like', '%' . $search . '%');
            });
        }

        // --- Lógica de Filtro por Tipo (diagram/manual) ---
        $filterType = $request->input('type');
        if ($filterType && in_array($filterType, ['diagram', 'manual'])) {
            $query->where('type', $filterType);
        }

        // --- Lógica de Filtro por Categoría de Máquina ---
        $filterCategory = $request->input('category');
        if ($filterCategory) {
            $query->where('machine_category', $filterCategory);
        }

        /**
         * Obtener los diagramas paginados
         * Paginación de 10 por página
         */
        $diagrams = $query->orderBy('name')->paginate(10);

        // Obtener todas las categorías de máquina únicas para el filtro (solo de diagramas activos)
        $availableCategories = Diagram::where('is_active', true)
                                      ->distinct('machine_category')
                                      ->pluck('machine_category')
                                      ->filter() // Eliminar nulos
                                      ->sort(); // Ordenar alfabéticamente

        // Pasar los datos a la vista
        return view('diagrams.index', [
            'diagrams' => $diagrams,
            'search_query' => $search,
            'selected_type' => $filterType,
            'selected_category' => $filterCategory,
            'available_categories' => $availableCategories
        ]);
    }

    /**
     * Muestra la página de detalles de un diagrama/manual específico, incluyendo el QR.
     *
     * @param Diagram $diagram
     * @return \Illuminate\View\View
     */
    public function show(Diagram $diagram)
    {
        // Asegurarse de que el diagrama esté activo para ser visto por usuarios
        if (!$diagram->is_active && !Auth::user()->hasRole('admin')) { // Admins pueden ver inactivos
            abort(404); // O 403
        }

        return view('diagrams.show', compact('diagram'));
    }

    /**
     * Sirve el archivo real del diagrama/manual.
     * Esta es la URL a la que apuntará el código QR.
     *
     * @param Diagram $diagram
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function serveFile(Diagram $diagram)
    {
        
        // Asegurarse de que el diagrama esté activo
        if (!$diagram->is_active && !Auth::user()->hasRole('admin')) {
            abort(403, 'Acceso no autorizado al archivo.');
        }

        // Verifica si el archivo existe en el disco público de storage
        if (!Storage::disk('public')->exists($diagram->file_path)) {
            Log::error("Archivo de diagrama no encontrado: " . $diagram->file_path);
            abort(404, 'Archivo no encontrado en el servidor.');
        }

        // Obtiene la ruta física completa del archivo
        $path = Storage::disk('public')->path($diagram->file_path);

        // Retorna el archivo al navegador. Laravel enviará las cabeceras correctas.
        return response()->file($path);
    }
        /**
     * Genera un PDF con el Código QR de un diagrama específico para impresión.
     * @param Diagram $diagram
     * @return \Illuminate\Http\Response
     */
    public function generateQrPdf(Diagram $diagram)
    {
       if (!$diagram->is_active && !Auth::user()->hasRole('admin')) {
            abort(403, 'Acceso no autorizado al diagrama.');
        }

        // URL a la que apuntará el QR (la ruta protegida para servir el archivo)
        $qrContentUrl = route('diagrams.serve_file', $diagram->id);
        // https://es.stackoverflow.com/questions/309482/integraci%c3%b3n-laravel-dompdf-y-qrcode-simplesoftwareio
        // Generar el código QR como SVG (es vectorial y de alta calidad para PDF)
        $qrSvg = QrCode::size(200)->format('svg')->generate($qrContentUrl);

        // Preparar los datos para la vista Blade del PDF
        $data = [
            'diagramName' => $diagram->name,
            'diagramDescription' => $diagram->description,
            'machineCategory' => $diagram->machine_category,
            'qrCodeSvg' => $qrSvg,
            'qrContentUrl' => $qrContentUrl, // Por si quieres mostrar la URL debajo del QR
        ];

        // Cargar la vista Blade que contendrá el HTML del PDF
        // Esta vista será simple, solo con el contenido que quieres en el PDF
        $pdf = Pdf::loadView('diagrams.qr_pdf_template', $data);

        // Opciones de configuración del PDF (ej., tamaño de papel, orientación)
        // $pdf->setPaper('A4', 'portrait');

        // Retornar el PDF para descarga o vista en el navegador
        // return $pdf->download('qr_diagrama_' . Str::slug($diagram->name) . '.pdf'); // Para descargar
        return $pdf->stream('qr_diagrama_' . Str::slug($diagram->name) . '.pdf'); // Para ver en el navegado
    }
}