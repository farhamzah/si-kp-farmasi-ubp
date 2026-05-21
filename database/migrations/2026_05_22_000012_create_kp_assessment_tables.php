<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kp_assessment_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_period_id')->constrained('kp_periods')->cascadeOnDelete();
            $table->enum('assessor_type', ['pembimbing_dalam', 'pembimbing_lapangan', 'penguji']);
            $table->string('component_name');
            $table->text('description')->nullable();
            $table->decimal('weight', 6, 2)->default(0);
            $table->decimal('max_score', 6, 2)->default(100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kp_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_assignment_id')->constrained('kp_assignments')->cascadeOnDelete();
            $table->foreignId('kp_exam_id')->nullable()->constrained('kp_exams')->nullOnDelete();
            $table->foreignId('kp_assessment_component_id')->constrained('kp_assessment_components')->cascadeOnDelete();
            $table->foreignId('assessor_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('assessor_type', ['pembimbing_dalam', 'pembimbing_lapangan', 'penguji']);
            $table->decimal('score', 6, 2);
            $table->decimal('weighted_score', 8, 2)->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['draft', 'submitted', 'locked'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->unique(['kp_assignment_id', 'kp_assessment_component_id'], 'kp_scores_assignment_component_unique');
        });

        Schema::create('kp_final_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_assignment_id')->unique()->constrained('kp_assignments')->cascadeOnDelete();
            $table->decimal('final_score', 8, 2)->nullable();
            $table->string('final_grade')->nullable();
            $table->enum('status', ['draft', 'calculated', 'final', 'locked', 'published'])->default('draft');
            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('kp_score_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_assignment_id')->nullable()->constrained('kp_assignments')->cascadeOnDelete();
            $table->foreignId('kp_score_id')->nullable()->constrained('kp_scores')->nullOnDelete();
            $table->foreignId('kp_final_score_id')->nullable()->constrained('kp_final_scores')->nullOnDelete();
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
        Schema::dropIfExists('kp_score_logs');
        Schema::dropIfExists('kp_final_scores');
        Schema::dropIfExists('kp_scores');
        Schema::dropIfExists('kp_assessment_components');
    }
};
