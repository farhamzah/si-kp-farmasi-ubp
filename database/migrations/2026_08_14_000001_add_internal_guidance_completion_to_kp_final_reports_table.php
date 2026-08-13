<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kp_final_reports', function (Blueprint $table): void {
            if (! Schema::hasColumn('kp_final_reports', 'internal_guidance_completed_by')) {
                $table->foreignId('internal_guidance_completed_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('kp_final_reports', 'internal_guidance_completed_at')) {
                $table->timestamp('internal_guidance_completed_at')->nullable();
            }

            if (! Schema::hasColumn('kp_final_reports', 'internal_guidance_completion_note')) {
                $table->text('internal_guidance_completion_note')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('kp_final_reports', function (Blueprint $table): void {
            if (Schema::hasColumn('kp_final_reports', 'internal_guidance_completed_by')) {
                $table->dropConstrainedForeignId('internal_guidance_completed_by');
            }

            if (Schema::hasColumn('kp_final_reports', 'internal_guidance_completed_at')) {
                $table->dropColumn('internal_guidance_completed_at');
            }

            if (Schema::hasColumn('kp_final_reports', 'internal_guidance_completion_note')) {
                $table->dropColumn('internal_guidance_completion_note');
            }
        });
    }
};
