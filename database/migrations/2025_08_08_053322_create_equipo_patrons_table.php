<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('equipos_patrones', function (Blueprint $table) {
            $table->id();
            $table->string('identificador')->nullable(); // Identificador único del equipo
            $table->text('descripcion')->nullable();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('numero_serie')->nullable(); // Número de serie (opcionalmente único)
            $table->date('ultima_calibracion')->nullable(); // Fecha de última calibración
            $table->date('proxima_calibracion')->nullable(); // Fecha de próxima calibración (vigencia)
            $table->enum('estado', ['CUMPLE', 'NO CUMPLE', 'CUMPLE PARCIALMENTE']);

            $table->string('clasificacion_equipo', 10)->nullable(); // "CLASIFICACION DEL EQUIPO" (p.ej. P)
            // Intervalo de medición: min, max, y unidad (p.ej. -3.6, 30.6, "psi")
            $table->decimal('intervalo_min', 12, 4)->nullable();
            $table->decimal('intervalo_max', 12, 4)->nullable();
            $table->string('intervalo_unidad', 50)->nullable();

            $table->string('exactitud', 100)->nullable(); // "Exactitud" (texto libre por diferentes formatos)
            $table->string('folio_certificado', 50)->nullable(); // "FOLIO CERTIFICADO"
            $table->boolean('vigente')->nullable(); // "Vigente" (sí/no; se podrá mapear en la carga)
            $table->string('resguardo', 50)->nullable(); // "Resguardo" (ej. RLT)
            $table->string('activo_fijo', 50)->nullable(); // "ACTIVO FIJO"
            $table->string('inventario', 50)->nullable(); // "INVENTARIO"
            $table->text('observaciones')->nullable(); // "Observaciones"
            
            // Clave foránea al usuario que lo creó
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipo_patrones');
    }
};
