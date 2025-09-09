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
        Schema::table('sistemas', function (Blueprint $table) {
            // Elimina el índice unique de la columna 'clave'
            $table->dropUnique('sistemas_sistema_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sistemas', function (Blueprint $table) {
            // Vuelve a agregar el índice unique en la columna 'clave'
            $table->unique('sistema');
        });
    }
};
