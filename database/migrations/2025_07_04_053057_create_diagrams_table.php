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
        Schema::create('diagrams', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre legible del diagrama/manual (ej., "Diagrama Eléctrico Unidad 1")
            $table->string('file_path'); // Ruta relativa al archivo en el storage (ej., 'diagrams/abcde.pdf')
            $table->string('file_original_name'); // Nombre original del archivo subido (ej., "Diagrama_U1.pdf")
            $table->enum('type', ['diagram', 'manual']); // Distinción: diagrama o manual
            $table->string('machine_category')->nullable();
            $table->text('description')->nullable(); // Descripción detallada
            $table->boolean('is_active')->default(true); // Activo/inactivo en el sistema
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null'); // Quién lo subió
        
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // Para eliminación lógica
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagrams');
    }
};
