<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkDiagramsToSystems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pegd:link-diagrams-to-systems {--dry-run} {--reset : También reescribe los que ya tienen sistema_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detecta la clave del sistema en el nombre del diagrama y llena diagrams.sistema_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /*
            Cómo usar

            Vista previa:
            php artisan pegd:link-diagrams-to-systems --dry-run

            Actualizar solo los que están NULL:
            php artisan pegd:link-diagrams-to-systems

            Reescribir también los que ya tenían sistema:
            php artisan pegd:link-diagrams-to-systems --reset
        */
        $dry = $this->option('dry-run');
        $reset = $this->option('reset');

        $nullFilter = $reset ? '' : 'd.sistema_id IS NULL AND ';

        // ---------- PREVIEW (SELECT) ----------
        $previewSql = "
            SELECT m.diagram_id,
                   d.name,
                   s.clave AS clave_detectada,
                   m.sistema_id
            FROM (
              SELECT d.id AS diagram_id, s.id AS sistema_id
              FROM diagrams d
              JOIN sistemas s
                ON (" . ($reset ? '1=1' : 'd.sistema_id IS NULL') . ")
               AND UPPER(d.name) REGEXP CONCAT('(^|[^A-Z0-9])', s.clave, '([^A-Z0-9]|$)')
            ) AS m
            JOIN (
              SELECT d.id AS diagram_id
              FROM diagrams d
              JOIN sistemas s
                ON (" . ($reset ? '1=1' : 'd.sistema_id IS NULL') . ")
               AND UPPER(d.name) REGEXP CONCAT('(^|[^A-Z0-9])', s.clave, '([^A-Z0-9]|$)')
              GROUP BY d.id
              HAVING COUNT(*) = 1
            ) AS uniq ON uniq.diagram_id = m.diagram_id
            JOIN diagrams d ON d.id = m.diagram_id
            JOIN sistemas s ON s.id = m.sistema_id
            ORDER BY m.diagram_id
        ";

        // ---------- UPDATE ----------
        $updateSql = "
            UPDATE diagrams d
            JOIN (
              SELECT m.diagram_id, m.sistema_id
              FROM (
                SELECT d.id AS diagram_id, s.id AS sistema_id
                FROM diagrams d
                JOIN sistemas s
                  ON (" . ($reset ? '1=1' : 'd.sistema_id IS NULL') . ")
                 AND UPPER(d.name) REGEXP CONCAT('(^|[^A-Z0-9])', s.clave, '([^A-Z0-9]|$)')
              ) AS m
              JOIN (
                SELECT d.id AS diagram_id
                FROM diagrams d
                JOIN sistemas s
                  ON (" . ($reset ? '1=1' : 'd.sistema_id IS NULL') . ")
                 AND UPPER(d.name) REGEXP CONCAT('(^|[^A-Z0-9])', s.clave, '([^A-Z0-9]|$)')
                GROUP BY d.id
                HAVING COUNT(*) = 1
              ) AS uniq ON uniq.diagram_id = m.diagram_id
            ) AS one ON one.diagram_id = d.id
            SET d.sistema_id = one.sistema_id
            " . ($reset ? "" : "WHERE d.sistema_id IS NULL") . "
        ";

        if ($dry) {
            $rows = DB::select($previewSql);
            $this->table(['diagram_id','name','clave_detectada','sistema_id'], array_map(fn($r) => (array)$r, $rows));
            $this->info('Dry-run: no se escribió nada.');
            return Command::SUCCESS;
        }

        $affected = DB::affectingStatement($updateSql);
        $this->info("Listo. Registros actualizados: {$affected}");
        return Command::SUCCESS;
    }
}
