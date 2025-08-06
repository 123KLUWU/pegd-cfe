<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TemplatePrefilledData; // Tu modelo de prellenados
use App\Models\Tag; // Tu modelo Tag (que ahora representa instrumentos)
use Illuminate\Support\Facades\DB; // Para transacciones
use Illuminate\Support\Facades\Log; 

class MigratePrefilledDataTagToId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prefilled-data:migrate-tag-to-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates existing tag names in "name" column of prefilled data to their corresponding tag_id.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando migración de nombres de TAG a IDs y limpieza de la columna "name" en datos prellenados...');

        // Queremos procesar todos los registros, incluso los que ya tienen tag_id, para limpiar su 'name'.
        // Si solo quieres limpiar 'name' de los que ya tienen tag_id, puedes ajustar el where.
        $prefilledDataRecords = TemplatePrefilledData::all(); // Obtener todos los registros para limpiar su 'name'

        $totalRecords = $prefilledDataRecords->count();
        $updatedCount = 0;
        $nameCleanedCount = 0;
        $notFoundCount = 0;
        $skippedCount = 0; // Para errores al guardar

        if ($totalRecords === 0) {
            $this->info('No se encontraron registros de datos prellenados para procesar.');
            return Command::SUCCESS;
        }

        $this->withProgressBar($prefilledDataRecords, function ($record) use (&$updatedCount, &$nameCleanedCount, &$notFoundCount, &$skippedCount) {
            $originalName = $record->name; // El nombre original del registro de prellenado
            $originalTagId = $record->tag_id; // El tag_id original

            // --- LÓGICA DE LIMPIEZA DE LA CADENA 'name' ---
            $cleanedName = preg_replace('/\s+/', ' ', $originalName);
            $cleanedName = trim($cleanedName);
            // ------------------------------------------------

            $tagInstrumento = null;
            // Solo buscar el TAG si el 'name' original no era nulo y está limpio
            if (!empty($cleanedName)) {
                $tagInstrumento = Tag::where('tag', $cleanedName)->first();
            }

            try {
                DB::transaction(function () use ($record, $originalName, $cleanedName, $originalTagId, $tagInstrumento, &$updatedCount, &$nameCleanedCount, &$notFoundCount) {
                    $needsSave = false;

                    // 1. Limpiar y actualizar la columna 'name' si es necesario
                    if ($originalName !== $cleanedName) {
                        $record->name = $cleanedName;
                        $nameCleanedCount++;
                        $needsSave = true;
                    }

                    // 2. Asignar 'tag_id' si aún no está asignado y se encontró el TAG
                    if (is_null($record->tag_id)) { // Solo si tag_id es nulo
                        if ($tagInstrumento) {
                            $record->tag_id = $tagInstrumento->id;
                            $updatedCount++;
                            $needsSave = true;
                        } else {
                            // Si tag_id es nulo y no se encontró el TAG, se cuenta como no encontrado
                            $notFoundCount++;
                            Log::warning("TAG '{$originalName}' (limpio: '{$cleanedName}') en registro ID {$record->id} no encontrado en la tabla de instrumentos. Tag_id permanece nulo.");
                        }
                    }

                    // Guardar el registro si hubo algún cambio
                    if ($needsSave) {
                        $record->save();
                    }
                });
            } catch (\Exception $e) {
                $this->warn("Error al procesar registro ID {$record->id} ('{$originalName}'): {$e->getMessage()}. Saltando.");
                Log::error("Error al procesar prefilled data ID {$record->id} para TAG '{$originalName}': {$e->getMessage()}");
                $skippedCount++;
            }
        });

        $this->newLine();
        $this->info("Migración y limpieza completada:");
        $this->info("  - Registros procesados: {$totalRecords}");
        $this->info("  - Nombres de TAG limpiados/normalizados: {$nameCleanedCount}");
        $this->info("  - TAG IDs asignados (si eran nulos): {$updatedCount}");
        $this->info("  - TAGs no encontrados (y no se pudo asignar ID): {$notFoundCount}");
        $this->info("  - Errores/Saltados: {$skippedCount}");

        return Command::SUCCESS;
    }
}
