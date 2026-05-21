<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('last_login_at');
            $table->string('avatar_disk')->nullable()->default('local')->after('avatar_path');
            $table->string('avatar_original_filename')->nullable()->after('avatar_disk');
            $table->string('avatar_mime')->nullable()->after('avatar_original_filename');
            $table->unsignedBigInteger('avatar_size')->nullable()->after('avatar_mime');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_path',
                'avatar_disk',
                'avatar_original_filename',
                'avatar_mime',
                'avatar_size',
            ]);
        });
    }
};
