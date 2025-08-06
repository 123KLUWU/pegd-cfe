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
        Schema::table('tags', function (Blueprint $table) {
            /*
            *   hola
            *   creo que esta migracion necesita mas explicacion que las anteriores
            *   la razon es muy simple, una modificacion que me pidieron
            *   como no queria refactorizar media aplicacion solo modifique las tablas de la base de datos
            *   la tabla de tags ahora funciona como la tabla de los instrumentos.
            *   mucha suerte.
            */
            // Añadir la clave foránea a 'unidades'
            // Asegúrate de que la tabla 'unidades' exista antes de esta migración.


            // Añadir campos de control de calibración y descripción del instrumento
            $table->timestamp('last_calibration_date')->nullable();
            $table->text('description')->nullable()->after('last_calibration_date');

            /* 

            $table->string('model')->nullable()->after('description');
            $table->string('serial_number')->nullable()->after('model');
            */

            // Añadir soft deletes si no lo tenía
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('last_calibration_date');
            $table->dropColumn('description');
            /*
            $table->dropColumn('model');
            $table->dropColumn('serial_number');
            */
            $table->dropSoftDeletes(); 
        });
    }
};
