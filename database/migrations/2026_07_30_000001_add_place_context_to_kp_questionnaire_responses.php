<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kp_questionnaire_responses', function (Blueprint $table): void {
            $table->foreignId('kp_place_id')->nullable()->after('kp_assignment_id')->constrained('kp_places')->nullOnDelete();
            $table->foreignId('kp_period_id')->nullable()->after('kp_place_id')->constrained('kp_periods')->nullOnDelete();

            $table->unique(
                ['kp_questionnaire_id', 'kp_place_id', 'kp_period_id', 'respondent_user_id'],
                'kp_q_response_place_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('kp_questionnaire_responses', function (Blueprint $table): void {
            $table->dropUnique('kp_q_response_place_unique');
            $table->dropConstrainedForeignId('kp_place_id');
            $table->dropConstrainedForeignId('kp_period_id');
        });
    }
};
