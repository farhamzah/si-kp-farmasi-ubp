<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kp_exam_invitation_signatories', function (Blueprint $table) {
            $table->id();
            $table->string('coordinator_name');
            $table->string('coordinator_nuptk')->nullable();
            $table->string('head_program_name');
            $table->string('head_program_nuptk')->nullable();
            $table->string('dean_name');
            $table->string('dean_nuptk')->nullable();
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kp_exam_invitation_signatories');
    }
};
