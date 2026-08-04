<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kp_periods', function (Blueprint $table): void {
            $table->boolean('score_visible_to_students')->default(false)->after('status');
        });

        Schema::create('kp_score_visibility_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kp_period_id')->constrained('kp_periods')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->boolean('can_view');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['kp_period_id', 'student_id'], 'kp_score_visibility_period_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kp_score_visibility_overrides');

        Schema::table('kp_periods', function (Blueprint $table): void {
            $table->dropColumn('score_visible_to_students');
        });
    }
};
