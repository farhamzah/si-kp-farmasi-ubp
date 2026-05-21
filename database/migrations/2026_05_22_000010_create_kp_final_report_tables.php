<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kp_final_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_assignment_id')->unique()->constrained('kp_assignments')->cascadeOnDelete();
            $table->unsignedInteger('current_version')->default(1);
            $table->enum('status', ['draft', 'menunggu_review', 'revisi', 'disetujui', 'ditolak'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('kp_final_report_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_final_report_id')->constrained('kp_final_reports')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('file_disk')->default('local');
            $table->string('file_mime')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('uploaded_at');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['kp_final_report_id', 'version'], 'kp_final_report_file_version_unique');
        });

        Schema::create('kp_final_report_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_final_report_id')->constrained('kp_final_reports')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kp_final_report_logs');
        Schema::dropIfExists('kp_final_report_files');
        Schema::dropIfExists('kp_final_reports');
    }
};
