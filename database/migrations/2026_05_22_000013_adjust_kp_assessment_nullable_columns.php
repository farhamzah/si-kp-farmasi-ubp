<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE kp_scores MODIFY kp_assignment_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE kp_score_logs MODIFY kp_assignment_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        // Kept intentionally empty; the base migration defines the desired schema for fresh installs.
    }
};
