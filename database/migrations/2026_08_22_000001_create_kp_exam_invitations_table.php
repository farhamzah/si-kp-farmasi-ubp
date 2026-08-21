<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kp_exam_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kp_exam_id')->unique()->constrained('kp_exams')->cascadeOnDelete();
            $table->string('letter_number')->unique();
            $table->string('verification_code', 64)->unique();
            $table->string('coordinator_name');
            $table->string('coordinator_nuptk')->nullable();
            $table->string('head_program_name');
            $table->string('head_program_nuptk')->nullable();
            $table->string('dean_name');
            $table->string('dean_nuptk')->nullable();
            $table->string('status')->default('published');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kp_exam_invitations');
    }
};
