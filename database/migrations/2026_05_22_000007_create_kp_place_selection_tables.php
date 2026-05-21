<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kp_place_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_period_id')->constrained('kp_periods')->cascadeOnDelete();
            $table->foreignId('kp_registration_id')->constrained('kp_registrations')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('kp_place_id')->constrained('kp_places')->cascadeOnDelete();
            $table->foreignId('kp_place_quota_id')->constrained('kp_place_quotas')->cascadeOnDelete();
            $table->timestamp('selected_at')->nullable();
            $table->foreignId('selected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['aktif', 'dibatalkan', 'dipindahkan'])->default('aktif');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('moved_from_selection_id')->nullable()->constrained('kp_place_selections')->nullOnDelete();
            $table->string('active_key')->nullable()->unique();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['kp_period_id', 'student_id', 'status'], 'kp_selection_period_student_status_idx');
        });

        Schema::create('kp_selection_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_period_id')->nullable()->constrained('kp_periods')->cascadeOnDelete();
            $table->foreignId('kp_registration_id')->nullable()->constrained('kp_registrations')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
            $table->foreignId('kp_place_id')->nullable()->constrained('kp_places')->nullOnDelete();
            $table->foreignId('kp_place_quota_id')->nullable()->constrained('kp_place_quotas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('status');
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('kp_waiting_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_period_id')->constrained('kp_periods')->cascadeOnDelete();
            $table->foreignId('kp_registration_id')->constrained('kp_registrations')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->enum('status', ['menunggu', 'sudah_memilih', 'dibatalkan'])->default('menunggu');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['kp_period_id', 'student_id'], 'kp_waiting_period_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kp_waiting_lists');
        Schema::dropIfExists('kp_selection_logs');
        Schema::dropIfExists('kp_place_selections');
    }
};
