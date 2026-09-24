<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kp_exam_invitation_signatories', function (Blueprint $table): void {
            $table->foreignId('coordinator_lecturer_id')->nullable()->after('id');
            $table->foreignId('head_program_lecturer_id')->nullable()->after('coordinator_nuptk');
            $table->foreignId('dean_lecturer_id')->nullable()->after('head_program_nuptk');
            $table->foreign('coordinator_lecturer_id', 'kp_inv_sign_coord_lecturer_fk')->references('id')->on('lecturers')->nullOnDelete();
            $table->foreign('head_program_lecturer_id', 'kp_inv_sign_head_lecturer_fk')->references('id')->on('lecturers')->nullOnDelete();
            $table->foreign('dean_lecturer_id', 'kp_inv_sign_dean_lecturer_fk')->references('id')->on('lecturers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kp_exam_invitation_signatories', function (Blueprint $table): void {
            $table->dropForeign('kp_inv_sign_coord_lecturer_fk');
            $table->dropForeign('kp_inv_sign_head_lecturer_fk');
            $table->dropForeign('kp_inv_sign_dean_lecturer_fk');
            $table->dropColumn(['coordinator_lecturer_id', 'head_program_lecturer_id', 'dean_lecturer_id']);
        });
    }
};
