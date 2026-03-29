<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->string('original_query');
            $table->string('normalized_query')->nullable();
            $table->unsignedBigInteger('tmdb_id')->nullable();
            $table->string('media_type', 10)->nullable();
            $table->string('resolved_title')->nullable();
            $table->string('status', 20)->default('pending');
            $table->boolean('was_corrected')->default(false);
            $table->string('corrected_query')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['import_batch_id', 'status']);
            $table->unique(['import_batch_id', 'line_number'], 'uq_import_item_batch_line');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_items');
    }
};

