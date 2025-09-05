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
        Schema::table('diagrams', function (Blueprint $table) {
            $table->foreignId('sistema_id')->nullable()->constrained('sistemas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagrams', function (Blueprint $table) {
            $table->dropForeign(['sistema_id']); // Eliminar FK primero
            $table->dropColumn('sistema_id');
        });
    }
};
