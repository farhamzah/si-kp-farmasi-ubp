<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kp_report_guidance_logs', function (Blueprint $table): void {
            $table->string('document_url', 2048)->nullable()->after('student_note');
            $table->string('document_label')->nullable()->after('document_url');
        });
    }

    public function down(): void
    {
        Schema::table('kp_report_guidance_logs', function (Blueprint $table): void {
            $table->dropColumn(['document_url', 'document_label']);
        });
    }
};
