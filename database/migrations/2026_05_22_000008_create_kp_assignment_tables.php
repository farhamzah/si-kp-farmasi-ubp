<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kp_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_period_id')->constrained('kp_periods')->cascadeOnDelete();
            $table->foreignId('kp_registration_id')->constrained('kp_registrations')->cascadeOnDelete();
            $table->foreignId('kp_place_selection_id')->nullable()->constrained('kp_place_selections')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('kp_place_id')->constrained('kp_places')->cascadeOnDelete();
            $table->foreignId('internal_supervisor_id')->nullable()->constrained('lecturers')->nullOnDelete();
            $table->foreignId('field_supervisor_id')->nullable()->constrained('field_supervisors')->nullOnDelete();
            $table->enum('status', ['draft', 'menunggu_pembimbing', 'aktif', 'berjalan', 'selesai', 'dibatalkan'])->default('menunggu_pembimbing');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->string('active_key')->nullable()->unique();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['kp_period_id', 'student_id', 'status'], 'kp_assignment_period_student_status_idx');
        });

        Schema::create('kp_assignment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_assignment_id')->constrained('kp_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->foreignId('old_internal_supervisor_id')->nullable()->constrained('lecturers')->nullOnDelete();
            $table->foreignId('new_internal_supervisor_id')->nullable()->constrained('lecturers')->nullOnDelete();
            $table->foreignId('old_field_supervisor_id')->nullable()->constrained('field_supervisors')->nullOnDelete();
            $table->foreignId('new_field_supervisor_id')->nullable()->constrained('field_supervisors')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('kp_place_field_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_place_id')->constrained('kp_places')->cascadeOnDelete();
            $table->foreignId('field_supervisor_id')->constrained('field_supervisors')->cascadeOnDelete();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['kp_place_id', 'field_supervisor_id'], 'kp_place_field_supervisor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kp_place_field_supervisors');
        Schema::dropIfExists('kp_assignment_logs');
        Schema::dropIfExists('kp_assignments');
    }
};
