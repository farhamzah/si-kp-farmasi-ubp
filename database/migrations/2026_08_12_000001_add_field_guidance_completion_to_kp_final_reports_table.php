<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kp_final_reports', function (Blueprint $table): void {
            if (! Schema::hasColumn('kp_final_reports', 'field_guidance_completed_by')) {
                $table->foreignId('field_guidance_completed_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('kp_final_reports', 'field_guidance_completed_at')) {
                $table->timestamp('field_guidance_completed_at')->nullable();
            }

            if (! Schema::hasColumn('kp_final_reports', 'field_guidance_completion_note')) {
                $table->text('field_guidance_completion_note')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('kp_final_reports', function (Blueprint $table): void {
            if (Schema::hasColumn('kp_final_reports', 'field_guidance_completed_by')) {
                $table->dropConstrainedForeignId('field_guidance_completed_by');
            }

            if (Schema::hasColumn('kp_final_reports', 'field_guidance_completed_at')) {
                $table->dropColumn('field_guidance_completed_at');
            }

            if (Schema::hasColumn('kp_final_reports', 'field_guidance_completion_note')) {
                $table->dropColumn('field_guidance_completion_note');
            }
        });
    }
};
