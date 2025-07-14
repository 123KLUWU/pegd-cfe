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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Visible name of the template (e.g., "Turbine Calibration Sheet A")
            $table->string('google_drive_id'); // ID of the master template file in Google Drive
            $table->enum('type', ['document', 'spreadsheets']); // Type of template (document or spreadsheets)
            $table->json('mapping_rules_json')->nullable(); // JSON with mapping rules for data to cells/placeholders
            $table->text('description')->nullable(); // Detailed description of the template
            $table->boolean('is_active')->default(true); // Controls if the template is available for generation
            // Foreign key to the 'users' table, assuming 'users' table already exists.
            // If the user who created the template is deleted, this field will be set to NULL.
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
        Schema::dropIfExists('templates');
    }
};
