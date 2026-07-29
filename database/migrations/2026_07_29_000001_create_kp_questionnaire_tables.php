<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kp_questionnaires', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kp_period_id')->nullable()->constrained('kp_periods')->nullOnDelete();
            $table->string('audience', 40);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('aktif');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['audience', 'status'], 'kp_questionnaires_audience_status_idx');
            $table->index(['kp_period_id', 'audience'], 'kp_questionnaires_period_audience_idx');
        });

        Schema::create('kp_questionnaire_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kp_questionnaire_id')->constrained('kp_questionnaires')->cascadeOnDelete();
            $table->string('section')->nullable();
            $table->text('question_text');
            $table->string('answer_type', 30)->default('scale');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 20)->default('aktif');
            $table->timestamps();

            $table->index(['kp_questionnaire_id', 'status', 'sort_order'], 'kp_questionnaire_questions_lookup');
        });

        Schema::create('kp_questionnaire_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kp_questionnaire_id')->constrained('kp_questionnaires')->cascadeOnDelete();
            $table->foreignId('kp_assignment_id')->nullable()->constrained('kp_assignments')->nullOnDelete();
            $table->foreignId('respondent_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('respondent_role', 40);
            $table->string('status', 20)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['kp_questionnaire_id', 'kp_assignment_id', 'respondent_user_id'], 'kp_questionnaire_response_unique');
            $table->index(['respondent_user_id', 'respondent_role'], 'kp_q_response_user_role_idx');
            $table->index(['status', 'submitted_at'], 'kp_q_response_status_submitted_idx');
        });

        Schema::create('kp_questionnaire_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kp_questionnaire_response_id')->constrained('kp_questionnaire_responses')->cascadeOnDelete();
            $table->foreignId('kp_questionnaire_question_id')->constrained('kp_questionnaire_questions')->cascadeOnDelete();
            $table->text('answer_value')->nullable();
            $table->timestamps();

            $table->unique(['kp_questionnaire_response_id', 'kp_questionnaire_question_id'], 'kp_questionnaire_answer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kp_questionnaire_answers');
        Schema::dropIfExists('kp_questionnaire_responses');
        Schema::dropIfExists('kp_questionnaire_questions');
        Schema::dropIfExists('kp_questionnaires');
    }
};
