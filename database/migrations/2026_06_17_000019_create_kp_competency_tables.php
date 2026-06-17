<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kp_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_period_id')->nullable()->constrained('kp_periods')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kp_period_id', 'status', 'sort_order'], 'kp_competencies_period_status_sort_idx');
        });

        Schema::create('kp_competency_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_assignment_id')->constrained('kp_assignments')->cascadeOnDelete();
            $table->foreignId('kp_competency_id')->constrained('kp_competencies')->cascadeOnDelete();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('achieved_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['kp_assignment_id', 'kp_competency_id'], 'kp_competency_assignment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kp_competency_achievements');
        Schema::dropIfExists('kp_competencies');
    }
};
