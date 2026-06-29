<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kp_competencies', function (Blueprint $table) {
            $table->json('place_types')->nullable()->after('place_type');
        });
    }

    public function down(): void
    {
        Schema::table('kp_competencies', function (Blueprint $table) {
            $table->dropColumn('place_types');
        });
    }
};
