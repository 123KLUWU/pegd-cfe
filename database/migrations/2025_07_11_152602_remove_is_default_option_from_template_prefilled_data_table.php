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
        Schema::table('template_prefilled_data', function (Blueprint $table) {
            // Check if the unique index exists before dropping it
            // This might vary based on your database system or Laravel version
            // You can check 'SHOW INDEXES FROM template_prefilled_data;' in MySQL CLI
            // If you had a unique constraint like this:
            // $table->dropUnique('template_prefilled_data_template_id_is_default_option_unique');

            $table->dropColumn('is_default_option');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_prefilled_data', function (Blueprint $table) {
            $table->boolean('is_default_option')->default(false)->after('data_json');
            // If you had a unique constraint, add it back here
            // $table->unique(['template_id', 'is_default_option'], 'unique_default_per_template')->where('is_default_option', 1);
        });
    }
};