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
    return view('welcome');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

// Rutas de Administración de Plantillas
Route::middleware(['auth', 'role:admin|permission:manage templates'])->prefix('admin/templates')->group(function () {
    Route::get('/', [AdminTemplateController::class, 'index'])->name('admin.templates.index');
    Route::get('/create', [AdminTemplateController::class, 'create'])->name('admin.templates.create');
    Route::post('/', [AdminTemplateController::class, 'store'])->name('admin.templates.store');
    Route::get('/{template}/edit', [AdminTemplateController::class, 'edit'])->name('admin.templates.edit');
    Route::put('/{template}', [AdminTemplateController::class, 'update'])->name('admin.templates.update');
    Route::post('/{template}/delete', [AdminTemplateController::class, 'delete'])->name('admin.templates.delete');
    Route::post('/{id}/restore', [AdminTemplateController::class, 'restore'])->name('admin.templates.restore');
    Route::delete('/{id}/force-delete', [AdminTemplateController::class, 'forceDelete'])->name('admin.templates.force_delete'); // Usa método DELETE HTTP
    Route::prefix('{template}/prefilled-data')->group(function () {
        Route::get('/create', [TemplatePrefilledDataController::class, 'create'])->name('admin.templates.prefilled-data.create');
        Route::post('/', [TemplatePrefilledDataController::class, 'store'])->name('admin.templates.prefilled-data.store');
        Route::get('/{prefilledData}/edit', [TemplatePrefilledDataController::class, 'edit'])->name('admin.templates.prefilled-data.edit');
        Route::put('/{prefilledData}', [TemplatePrefilledDataController::class, 'update'])->name('admin.templates.prefilled-data.update');
    
    });
});

// Rutas de Usuario (requieren autenticación)
Route::middleware(['auth'])->group(function () {
    // Menú principal de plantillas
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');

    // Rutas para generar documentos (POST)
    Route::post('/documents/generate/blank', [DocumentGenerationController::class, 'generateBlank'])->name('documents.generate.blank');
    Route::post('/documents/generate/predefined', [DocumentGenerationController::class, 'generatePredefined'])->name('documents.generate.predefined');
    Route::post('/documents/generate/custom', [DocumentGenerationController::class, 'generateCustom'])->name('documents.generate.custom');

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