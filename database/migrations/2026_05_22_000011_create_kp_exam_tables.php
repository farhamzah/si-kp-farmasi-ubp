<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kp_exam_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_assignment_id')->unique()->constrained('kp_assignments')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['draft', 'diajukan', 'disetujui', 'dijadwalkan', 'revisi', 'ditolak', 'dibatalkan'])->default('draft');
            $table->text('request_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });

        Schema::create('kp_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_exam_request_id')->constrained('kp_exam_requests')->cascadeOnDelete();
            $table->foreignId('kp_assignment_id')->unique()->constrained('kp_assignments')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('lecturers')->cascadeOnDelete();
            $table->foreignId('examiner_id')->constrained('lecturers')->cascadeOnDelete();
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('mode', ['offline', 'online', 'hybrid']);
            $table->string('room')->nullable();
            $table->string('meeting_link')->nullable();
            $table->enum('status', ['dijadwalkan', 'selesai', 'dibatalkan', 'ditunda'])->default('dijadwalkan');
            $table->foreignId('scheduled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('kp_exam_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_exam_request_id')->nullable()->constrained('kp_exam_requests')->cascadeOnDelete();
            $table->foreignId('kp_exam_id')->nullable()->constrained('kp_exams')->cascadeOnDelete();
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
        Schema::dropIfExists('kp_exam_logs');
        Schema::dropIfExists('kp_exams');
        Schema::dropIfExists('kp_exam_requests');
    }
};
