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
        Schema::create('template_prefilled_data', function (Blueprint $table) {
            $table->id();
            // Foreign key to the 'templates' table.
            // If a template is deleted (soft-deleted), its associated prefilled data should also be soft-deleted.
            // Or, if physical deletion is expected for templates, use onDelete('cascade').
            $table->foreignId('template_id')->constrained('templates')->onDelete('cascade'); // If template is physically deleted, delete prefilled data

            $table->string('name'); // Name of this specific set of prefilled data (e.g., "Data for Machine #123")
            $table->text('description')->nullable(); // Description for the administrator
            $table->json('data_json'); // JSON containing the actual values for prefilling
            $table->boolean('is_default_option')->default(false); // If it's the default option for its template

            // Foreign keys for generic data (e.g., tags, units, systems, services)
            // Assuming these tables exist (e.g., 'tags', 'unidades', 'sistemas', 'servicios')
            // And that their IDs are unsigned big integers.
            // If these are optional, add nullable().
            $table->foreignId('tag_id')->nullable()->constrained('tags')->onDelete('set null');
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->onDelete('set null');
            $table->foreignId('sistema_id')->nullable()->constrained('sistemas')->onDelete('set null');
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->onDelete('set null');

            // Foreign key to the 'users' table (who created this prefilled data set)
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at column for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_prefilled_data');
    }
};
