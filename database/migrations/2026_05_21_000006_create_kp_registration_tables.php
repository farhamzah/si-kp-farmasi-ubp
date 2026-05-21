<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kp_document_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_period_id')->constrained('kp_periods')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->string('allowed_file_types')->default('pdf,jpg,jpeg,png');
            $table->unsignedInteger('max_file_size_mb')->default(5);
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kp_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_period_id')->constrained('kp_periods')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('registration_number')->nullable()->unique();
            $table->enum('status', ['draft', 'menunggu_verifikasi', 'revisi', 'terverifikasi', 'ditolak', 'dibatalkan'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->timestamps();

            $table->unique(['kp_period_id', 'student_id']);
        });

        Schema::create('kp_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_registration_id')->constrained('kp_registrations')->cascadeOnDelete();
            $table->foreignId('kp_document_requirement_id')->constrained('kp_document_requirements')->cascadeOnDelete();
            $table->string('original_filename')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_disk')->default('local');
            $table->string('file_mime')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->enum('status', ['belum_upload', 'menunggu', 'disetujui', 'revisi', 'ditolak'])->default('belum_upload');
            $table->text('review_note')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['kp_registration_id', 'kp_document_requirement_id'], 'kp_doc_registration_requirement_unique');
        });

        Schema::create('kp_registration_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_registration_id')->constrained('kp_registrations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kp_registration_logs');
        Schema::dropIfExists('kp_documents');
        Schema::dropIfExists('kp_registrations');
        Schema::dropIfExists('kp_document_requirements');
    }
};
