<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tag; // Tu modelo Tag (que representa instrumentos)
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanInstrumentTags extends Command
{
          /**
         * The name and signature of the console command.
         *
         * @var string
         */
        protected $signature = 'instrumentos:clean-tags';

        /**
         * The console command description.
         *
         * @var string
         */
        protected $description = 'Cleans tag column in the "tags" table (instruments) by removing newlines and extra spaces.';

        /**
         * Execute the console command.
         */
        public function handle()
        {
            $this->info('Iniciando limpieza de la columna "tag" en la tabla de instrumentos...');

            $totalTags = Tag::count();
            $cleanedCount = 0;
            $skippedCount = 0;

            if ($totalTags === 0) {
                $this->info('No se encontraron instrumentos para limpiar.');
                return Command::SUCCESS;
            }

            $tagsToClean = Tag::all(); // Obtener todos los TAGs

            $this->withProgressBar($tagsToClean, function ($tag) use (&$cleanedCount, &$skippedCount) {
                $originalTagValue = $tag->tag;

                // Aplicar la misma lógica de limpieza que para los datos de prellenado
                $cleanedTagValue = preg_replace('/\s+/', ' ', $originalTagValue);
                $cleanedTagValue = trim($cleanedTagValue);

                if ($originalTagValue !== $cleanedTagValue) {
                    try {
                        DB::transaction(function () use ($tag, $cleanedTagValue) {
                            $tag->tag = $cleanedTagValue;
                            $tag->save();
                        });
                        $cleanedCount++;
                    } catch (\Exception $e) {
                        $this->warn("Error al limpiar TAG ID {$tag->id} ('{$originalTagValue}'): {$e->getMessage()}. Saltando.");
                        Log::error("Error al limpiar TAG ID {$tag->id} ('{$originalTagValue}'): {$e->getMessage()}");
                        $skippedCount++;
                    }
                } else {
                    $skippedCount++; // No se necesita limpieza, se salta
                }
            });

            $this->newLine();
            $this->info("Limpieza completada:");
            $this->info("  - TAGs procesados: {$totalTags}");
            $this->info("  - TAGs limpiados/normalizados: {$cleanedCount}");
            $this->info("  - TAGs sin cambios (ya limpios o error): {$skippedCount}");

            return Command::SUCCESS;
        }
}
