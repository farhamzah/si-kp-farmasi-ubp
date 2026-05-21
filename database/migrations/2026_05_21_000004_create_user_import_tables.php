<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imported_by')->constrained('users')->cascadeOnDelete();
            $table->enum('import_type', ['mahasiswa', 'dosen', 'pembimbing_lapangan', 'mixed']);
            $table->string('original_filename')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('success_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->enum('status', ['draft', 'processing', 'completed', 'completed_with_errors', 'failed'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('user_import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('user_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('identifier')->nullable();
            $table->text('error_message');
            $table->json('row_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_import_errors');
        Schema::dropIfExists('user_import_batches');
    }
};
