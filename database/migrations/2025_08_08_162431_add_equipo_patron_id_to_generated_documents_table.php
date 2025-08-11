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
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->foreignId('equipo_patron_id')->nullable()->constrained('equipos_patrones')->onDelete('set null')->after('unidad_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->dropForeign(['equipo_patron_id']);
            $table->dropColumn('equipo_patron_id');
        });
    }
};
