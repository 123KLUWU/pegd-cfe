<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\HomeController;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Http\Controllers\Admin\TemplatePrefilledDataController;
use App\Http\Controllers\TemplateController; // Para mostrar la lista de plantillas
use App\Http\Controllers\DocumentGenerationController; // Para la lógica de generación
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController; // Alias para evitar conflicto
use App\Http\Controllers\DiagramController;
use App\Http\Controllers\Admin\DiagramController as AdminDiagramController; // Alias
use App\Http\Controllers\Admin\UserController as AdminUserController; // Alias para evitar conflicto de nombres
use App\Http\Controllers\Admin\TemplatePrefilledDataController as AdminTemplatePrefilledDataController; // Alias
use App\Http\Controllers\UserTemplateController;
use App\Http\Controllers\ApiLookupController;
use App\Http\Controllers\UserPrefilledDataController;
use App\Http\Controllers\Admin\GeneratedDocumentController as AdminGeneratedDocumentController; // Alias para evitar conflicto
use App\Http\Controllers\UserGeneratedDocumentController;
use App\Http\Controllers\EquipoPatronController;
use App\Http\Controllers\Admin\EquipoPatronController as AdminEquipoPatronController;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {

    Route::resource('equipos-patrones', AdminEquipoPatronController::class)->names('admin.equipos-patrones')->parameters(['equipos-patrones' => 'equipo_patron']);
    
    Route::post('equipos-patrones/{id}/restore', [AdminEquipoPatronController::class, 'restore'])->name('admin.equipos-patrones.restore');
    
    Route::delete('equipos-patrones/{id}/force-delete', [AdminEquipoPatronController::class, 'forceDelete'])->name('admin.equipos-patrones.force_delete');
});

// Rutas de Usuarios (accesibles por todos los autenticados)
Route::middleware(['auth'])->group(function () {
    
    Route::get('/prefilled-data', [UserPrefilledDataController::class, 'index'])->name('prefilled-data.index');
    // Rutas para los documentos generados por el usuario
    Route::get('/my-documents', [UserGeneratedDocumentController::class, 'index'])->name('user.generated-documents.index');
    Route::get('/my-documents/{generated_document}', [UserGeneratedDocumentController::class, 'show'])->name('user.generated-documents.show');
    Route::post('/my-documents/{generated_document}/delete', [UserGeneratedDocumentController::class, 'destroy'])->name('user.generated-documents.destroy');
    Route::post('/my-documents/{id}/restore', [UserGeneratedDocumentController::class, 'restore'])->name('user.generated-documents.restore');

    Route::get('/prefilled-data/{prefilled_data}/generate-confirm', [UserPrefilledDataController::class, 'showGenerateConfirmationForm'])->name('prefilled-data.generate_confirmation_form');

    Route::post('/documents/generate/predefined', [DocumentGenerationController::class, 'generatePredefined'])->name('documents.generate.predefined');
    
    // ¡NUEVA RUTA! Ruta para el listado de documentos generados agrupados
    Route::get('/mydocuments/grouped', [UserGeneratedDocumentController::class, 'indexByUnitAndInstrument'])->name('user.generated-documents.grouped');

    Route::get('/documents/grouped', [UserGeneratedDocumentController::class, 'indexByUnit'])->name('user.generated-documents.grouped.units');
    Route::get('/my-documents/grouped/{unidad}', [UserGeneratedDocumentController::class, 'showInstrumentsByUnit'])->name('user.generated-documents.grouped.instruments');
    Route::get('/my-documents/grouped/{unidad}/{instrumento}', [UserGeneratedDocumentController::class, 'showDocumentsByUnitAndInstrument'])->name('user.generated-documents.grouped.documents');
});

Route::get('/generate-by-qr/{prefilled_data_id}', [DocumentGenerationController::class, 'generatePredefinedByQr'])->name('documents.generate.predefined_by_qr');

// Rutas de Administración de Documentos Generados
Route::middleware(['auth', 'role:admin|permission:view all documents'])->prefix('admin')->group(function () {

    Route::resource('generated-documents', AdminGeneratedDocumentController::class)->except(['create', 'store', 'edit', 'update']);

    // Rutas adicionales para soft delete (restore, force-delete)
    Route::post('generated-documents/{id}/restore', [AdminGeneratedDocumentController::class, 'restore'])->name('admin.generated-documents.restore');
    Route::delete('generated-documents/{id}/force-delete', [AdminGeneratedDocumentController::class, 'forceDelete'])->name('admin.generated-documents.force_delete');

    // Ruta para cambiar la visibilidad de un documento
    Route::post('generated-documents/{generated_document}/change-visibility', [AdminGeneratedDocumentController::class, 'changeVisibility'])->name('admin.generated-documents.change_visibility');
});
// routes/web.php (o routes/api.php)
Route::middleware(['auth'])->prefix('api/lookup')->group(function () {
    Route::get('tags', [ApiLookupController::class, 'getTags'])->name('api.lookup.tags');
    Route::get('unidades', [ApiLookupController::class, 'getUnidades'])->name('api.lookup.unidades');
});
// Rutas de Administración de Usuarios

Route::middleware(['auth', 'role:admin|permission:manage users'])->prefix('admin')->group(function () {
    // Usamos Route::resource para las acciones CRUD básicas
    Route::resource('users', AdminUserController::class);

    // Rutas adicionales para soft delete (restore, force-delete)
    // El 'destroy' del resource ya maneja el soft delete por defecto.
    Route::post('users/{id}/restore', [AdminUserController::class, 'restore'])->name('admin.users.restore');
    Route::delete('users/{id}/force-delete', [AdminUserController::class, 'forceDelete'])->name('admin.users.force_delete');

    // Rutas para aprobar y rechazar usuarios (si no están en el LoginController)
    // Se pueden llamar desde el listado de usuarios
    Route::post('users/{user}/approve', [AdminUserController::class, 'approveUser'])->name('admin.users.approve');
    Route::post('users/{user}/reject', [AdminUserController::class, 'rejectUser'])->name('admin.users.reject');
    


    Route::get('/users/all', [AdminController::class, 'manageUsers'])->name('admin.manage_users');
});

Route::get('/users/pending', [AdminController::class, 'showPendingUsers'])->name('admin.users.pending');

Route::middleware(['auth', 'role:admin|permission:manage diagrams'])->prefix('admin')->group(function () {
    // Rutas de recurso para la gestión de diagramas/manuales (CRUD)
    Route::resource('diagrams', AdminDiagramController::class)->except(['show']); // No usamos show() por defecto aquí, tendremos una ruta custom para ello

    // Rutas adicionales para soft delete y force delete (no incluidas en resource por defecto)
    Route::post('diagrams/{id}/restore', [AdminDiagramController::class, 'restore'])->name('admin.diagrams.restore');
    Route::delete('diagrams/{id}/force-delete', [AdminDiagramController::class, 'force_Delete'])->name('admin.diagrams.force_delete');
    Route::delete('diagrams/{id}/destroy', [AdminDiagramController::class, 'destroy'])->name('admin.diagrams.destroy');
    Route::post('diagrams/{id}/restore', [AdminDiagramController::class, 'restore'])->name('admin.diagrams.restore');
    // Si queremos un show custom (por ejemplo para ver el detalle y el QR)
    Route::get('diagrams/{diagram}', [AdminDiagramController::class, 'show'])->name('admin.diagrams.show');
});

Route::get('/diagrams/{diagram}/qr-pdf', [DiagramController::class, 'generateQrPdf'])->name('diagrams.generate_qr_pdf');

Route::middleware(['auth', 'role:admin|permission:manage diagrams'])->prefix('admin/diagrams')->group(function () {
    Route::get('/', [AdminDiagramController::class, 'index'])->name('admin.diagrams.index');
    Route::get('/create', [AdminDiagramController::class, 'create'])->name('admin.diagrams.create');
    Route::post('/', [AdminDiagramController::class, 'store'])->name('admin.diagrams.store');
    Route::get('/{diagram}/edit', [AdminDiagramController::class, 'edit'])->name('admin.diagrams.edit');
    Route::put('/{diagram}', [AdminDiagramController::class, 'update'])->name('admin.diagrams.update');
    Route::post('/{diagram}/delete', [AdminDiagramController::class, 'delete'])->name('admin.diagrams.delete');
    Route::post('/{id}/restore', [AdminDiagramController::class, 'restore'])->name('admin.diagrams.restore');
    Route::delete('/{id}/force-delete', [AdminDiagramController::class, 'force_delete'])->name('admin.diagrams.force_delete');
});

Route::middleware(['auth', 'permission:view diagrams'])->group(function () {
    Route::get('/diagrams', [DiagramController::class, 'index'])->name('diagrams.index');
    Route::get('/diagrams/{diagram}', [DiagramController::class, 'show'])->name('diagrams.show');
    Route::get('/diagrams/file/{diagram}', [DiagramController::class, 'serveFile'])->name('diagrams.serve_file'); // URL para el archivo real
});
Route::get('/', function () {
    // Si el usuario ya está autenticado, redirige al dashboard
    if (Auth::check()) {
        return redirect()->route('home');
    }
    // Si no, redirige al login
    return redirect()->route('login');
});
Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/settings', [HomeController::class, 'settings'])->name('settings');

Route::middleware(['auth'])->group(function () {
    // ... tus otras rutas de usuario (templates.index, documents.generate.*, etc.) ...

    // Rutas para servir/descargar copias de plantillas (accesibles por todos los autenticados)
    // El parámetro {template} usará Route Model Binding
    Route::get('templates/{template}/preview-pdf', [UserTemplateController::class, 'showPdfPreview'])->name('templates.show_pdf_preview');
    Route::get('templates/{template}/download-office', [UserTemplateController::class, 'downloadOfficeFile'])->name('templates.download_office');
});

Route::middleware(['auth', 'role:admin|permission:manage templates'])->prefix('admin/templates')->group(function () {
    Route::get('/', [AdminTemplateController::class, 'index'])->name('admin.templates.index');
    Route::get('/create', [AdminTemplateController::class, 'create'])->name('admin.templates.create');
    Route::post('/', [AdminTemplateController::class, 'store'])->name('admin.templates.store');
    Route::get('/{template}/show', [AdminTemplateController::class, 'show'])->name('admin.templates.show'); // Nueva ruta show
    Route::get('/{template}/edit', [AdminTemplateController::class, 'edit'])->name('admin.templates.edit');
    Route::put('/{template}', [AdminTemplateController::class, 'update'])->name('admin.templates.update');
    Route::post('/{template}/delete', [AdminTemplateController::class, 'destroy'])->name('admin.templates.destroy'); // Usar destroy para soft delete
    Route::post('/{id}/restore', [AdminTemplateController::class, 'restore'])->name('admin.templates.restore');
    Route::delete('/{id}/force-delete', [AdminTemplateController::class, 'forceDelete'])->name('admin.templates.force_delete'); // Usar DELETE HTTP
    Route::get('templates/{template}/preview-pdf', [AdminTemplateController::class, 'servePdfPreview'])->name('admin.templates.serve_pdf_preview');
    // Duplicar Plantilla (Función 5)
    Route::post('/{template}/duplicate', [AdminTemplateController::class, 'duplicate'])->name('admin.templates.duplicate');

    Route::prefix('/prefilled-data')->group(function () {
        Route::get('/', [AdminTemplatePrefilledDataController::class, 'index'])->name('admin.templates.prefilled-data.index');
        Route::get('/{template}/create', [AdminTemplatePrefilledDataController::class, 'create'])->name('admin.templates.prefilled-data.create');
        Route::post('/{template}', [AdminTemplatePrefilledDataController::class, 'store'])->name('admin.templates.prefilled-data.store');
    
    });
    Route::prefix('{prefilledData}/prefilled-data')->group(function () {
        Route::get('/edit', [AdminTemplatePrefilledDataController::class, 'edit'])->name('admin.templates.prefilled-data.edit');
        Route::put('/update', [AdminTemplatePrefilledDataController::class, 'update'])->name('admin.templates.prefilled-data.update');
    });
});

// Rutas de Usuario (requieren autenticación)
Route::middleware(['auth'])->group(function () {
    // Menú principal de plantillas
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');

    Route::get('/templates/{template}/qr-pdf', [DocumentGenerationController::class, 'generateQrPdf'])->name('templates.generate_qr_pdf');
    // Rutas para generar documentos (POST)
    Route::post('/documents/generate/blank', [DocumentGenerationController::class, 'generateBlank'])->name('documents.generate.blank');
    Route::post('/documents/generate/predefined', [DocumentGenerationController::class, 'generatePredefined'])->name('documents.generate.predefined');
    Route::post('/documents/generate/custom', [DocumentGenerationController::class, 'generateCustom'])->name('documents.generate.custom');
    Route::get('/documents/{TemplatePrefilledData}/qr-pdf', [UserPrefilledDataController::class, 'generateQrPdf'])->name('predefined.generate_qr_pdf');
    // Rutas para generar documentos (POST)
    // Ruta para mostrar el formulario de personalización (GET)
    // Usamos Route Model Binding para Template, asumiendo que el ID de la plantilla se pasa en la URL
    Route::get('/documents/customize/{template}', [DocumentGenerationController::class, 'showCustomizeForm'])->name('documents.customize.form');

    // Ruta de éxito después de la generación (GET, para redireccionar)
    Route::get('/documents/generated-success', [DocumentGenerationController::class, 'showGeneratedSuccess'])->name('documents.generated.success');
});

/**
 * 
 * rutas para configuracion google cloud 
 * 
 */

// Ruta para iniciar el proceso de autenticación de Google
Route::get('/google/auth', function (GoogleDriveService $googleService) {
    $client = $googleService->getClient();
    $authUrl = $client->createAuthUrl();
    return redirect()->to($authUrl);
})->name('google.auth');

// Ruta de callback a la que Google redirigirá después de la autenticación
Route::get('/google/callback', function (GoogleDriveService $googleService) {
    $client = $googleService->getClient();

    if (request()->has('code')) {
        $authCode = request('code');
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        // Guarda el refresh_token. ¡Este es el token que necesitas persistir!
        $refreshToken = $accessToken['refresh_token'] ?? null;

        if ($refreshToken) {
            // Guarda el refresh token en el .env
            $path = base_path('.env');
            if (File::exists($path)) {
                // Cuidado: Esta es una forma simple para desarrollo.
                // En producción, usa un sistema más robusto (DB, Hashicorp Vault, etc.)
                $envContent = File::get($path);
                if (Str::contains($envContent, 'GOOGLE_REFRESH_TOKEN=')) {
                    $envContent = preg_replace(
                        '/^GOOGLE_REFRESH_TOKEN=.*$/m',
                        'GOOGLE_REFRESH_TOKEN="' . $refreshToken . '"',
                        $envContent
                    );
                } else {
                    $envContent .= "\nGOOGLE_REFRESH_TOKEN=\"{$refreshToken}\"";
                }
                File::put($path, $envContent);
            }

            Log::info('Google Refresh Token obtenido y guardado.', ['refresh_token' => $refreshToken]);
            return "Autenticación exitosa. Refresh Token guardado en tu .env. Ya puedes usar las APIs.";
        } else {
            Log::error('No se pudo obtener el refresh token.');
            return "Error: No se pudo obtener el refresh token. Asegúrate de que el access type sea 'offline'.";
        }
    } else {
        Log::error('No se recibió código de autorización de Google.', ['request' => request()->all()]);
        return "Error: No se recibió código de autorización.";
    }
})->name('google.callback');