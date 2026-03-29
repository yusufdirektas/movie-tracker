<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('queued');
            $table->boolean('is_watched')->default(true);
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('processed_items')->default(0);
            $table->unsignedInteger('success_items')->default(0);
            $table->unsignedInteger('duplicate_items')->default(0);
            $table->unsignedInteger('not_found_items')->default(0);
            $table->unsignedInteger('error_items')->default(0);
            $table->unsignedInteger('skipped_items')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};

