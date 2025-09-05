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
        Schema::table('automatas', function (Blueprint $table) {
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automatas', function (Blueprint $table) {
            $table->dropForeign(['unidad_id']); // Eliminar FK primero
            $table->dropColumn('unidad_id');
        });
    }
};
