<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('diagrams', function (Blueprint $table) {
            $table->foreignId('unidad_id')
              ->nullable()
              ->after('id')
              ->constrained('unidades')
              ->nullOnDelete();

            $table->foreignId('classification_id')
              ->nullable()
              ->after('unidad_id')
              ->constrained('diagram_classifications')
              ->nullOnDelete();

            $table->foreignId('automata_id')
              ->nullable()
              ->after('classification_id')
              ->constrained('automatas')
              ->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::table('diagrams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unidad_id');
            $table->dropConstrainedForeignId('classification_id');
            $table->dropConstrainedForeignId('automata_id');
        });
    }
};