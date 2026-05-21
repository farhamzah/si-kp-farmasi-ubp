<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kp_logbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_assignment_id')->constrained('kp_assignments')->cascadeOnDelete();
            $table->date('activity_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('activity_title');
            $table->text('activity_description');
            $table->text('learning_outcome')->nullable();
            $table->text('obstacle')->nullable();
            $table->text('solution')->nullable();
            $table->string('evidence_original_filename')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('evidence_disk')->nullable();
            $table->string('evidence_mime')->nullable();
            $table->unsignedBigInteger('evidence_size')->nullable();
            $table->enum('status', ['draft', 'menunggu_validasi', 'disetujui', 'revisi', 'ditolak'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->text('validation_note')->nullable();
            $table->timestamps();

            $table->unique(['kp_assignment_id', 'activity_date'], 'kp_logbook_assignment_date_unique');
            $table->index(['status', 'activity_date']);
        });

        Schema::create('kp_logbook_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_logbook_id')->constrained('kp_logbooks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->enum('visibility', ['internal', 'visible_to_student'])->default('visible_to_student');
            $table->timestamps();
        });

        Schema::create('kp_logbook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_logbook_id')->constrained('kp_logbooks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kp_logbook_logs');
        Schema::dropIfExists('kp_logbook_comments');
        Schema::dropIfExists('kp_logbooks');
    }
};
