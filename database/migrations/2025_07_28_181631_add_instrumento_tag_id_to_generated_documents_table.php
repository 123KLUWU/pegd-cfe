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
            // Añadir la clave foránea a 'tags' (que ahora es 'instrumentos')
            // Renombrada a 'instrumento_tag_id' para claridad, aunque referencia a 'tags.id'
            $table->foreignId('instrumento_tag_id')->nullable()->constrained('tags')->onDelete('set null')->after('template_id');
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->onDelete('set null');
            $table->foreignId('prefilled_data_id')->nullable()->constrained('template_prefilled_data')->onDelete('set null')->after('unidad_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->dropForeign(['prefilled_data_id']);
            $table->dropColumn('prefilled_data_id');
            $table->dropForeign(['unidad_id']); // Eliminar FK primero
            $table->dropColumn('unidad_id');
            $table->dropForeign(['instrumento_tag_id']); // Eliminar FK primero
            $table->dropColumn('instrumento_tag_id');
        });
    }
};
