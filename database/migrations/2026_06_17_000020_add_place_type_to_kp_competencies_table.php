<?php

use App\Models\KpPlace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kp_competencies', function (Blueprint $table) {
            $table->enum('place_type', KpPlace::TYPES)->nullable()->after('kp_period_id');
            $table->index(['place_type', 'status', 'sort_order'], 'kp_competencies_place_type_status_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('kp_competencies', function (Blueprint $table) {
            $table->dropIndex('kp_competencies_place_type_status_sort_idx');
            $table->dropColumn('place_type');
        });
    }
};
