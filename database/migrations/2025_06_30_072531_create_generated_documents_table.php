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
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->string('google_drive_id')->unique(); // Google Drive ID of the generated document
            // Foreign key to 'users' (who generated it)
            // If the user is soft-deleted, we still keep the document, so set null on delete.
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            // Foreign key to 'templates' (which template was used)
            // If the template is soft-deleted, we still keep the generated document record.
            $table->foreignId('template_id')->nullable()->constrained('templates')->onDelete('set null');
            $table->string('title'); // Title of the generated document
            $table->enum('type', ['document', 'spreadsheets']); // Type of document (document or spreadsheets)
            $table->enum('visibility_status', ['public_editable', 'public_viewable', 'private_restricted'])->default('private_restricted');
            $table->timestamp('generated_at'); // When it was generated
            $table->timestamp('make_private_at')->nullable(); // When it should be made private (for scheduler)
            $table->json('data_values_json')->nullable(); // The actual JSON data used to fill this specific document instance (your JSON #2 copy)
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
