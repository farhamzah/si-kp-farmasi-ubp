<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kp_exams', function (Blueprint $table): void {
            $table->text('backdate_reason')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('kp_exams', function (Blueprint $table): void {
            $table->dropColumn('backdate_reason');
        });
    }
};
