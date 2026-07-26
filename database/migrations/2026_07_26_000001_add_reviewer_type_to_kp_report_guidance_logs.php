<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kp_report_guidance_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('kp_report_guidance_logs', 'reviewer_type')) {
                $table->string('reviewer_type', 20)->default('internal')->after('kp_assignment_id');
                $table->index(['kp_assignment_id', 'reviewer_type', 'status'], 'kp_report_guidance_assignment_reviewer_status_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kp_report_guidance_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('kp_report_guidance_logs', 'reviewer_type')) {
                $table->dropIndex('kp_report_guidance_assignment_reviewer_status_idx');
                $table->dropColumn('reviewer_type');
            }
        });
    }
};
