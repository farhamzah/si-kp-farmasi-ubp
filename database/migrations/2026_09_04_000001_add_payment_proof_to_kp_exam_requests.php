<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kp_exam_requests', function (Blueprint $table) {
            $table->string('payment_proof_url', 2048)->nullable()->after('request_note');
            $table->string('payment_proof_label')->nullable()->after('payment_proof_url');
            $table->string('payment_proof_original_filename')->nullable()->after('payment_proof_label');
            $table->string('payment_proof_path')->nullable()->after('payment_proof_original_filename');
            $table->string('payment_proof_disk')->nullable()->after('payment_proof_path');
            $table->string('payment_proof_mime')->nullable()->after('payment_proof_disk');
            $table->unsignedBigInteger('payment_proof_size')->nullable()->after('payment_proof_mime');
        });
    }

    public function down(): void
    {
        Schema::table('kp_exam_requests', function (Blueprint $table) {
            $table->dropColumn([
                'payment_proof_url',
                'payment_proof_label',
                'payment_proof_original_filename',
                'payment_proof_path',
                'payment_proof_disk',
                'payment_proof_mime',
                'payment_proof_size',
            ]);
        });
    }
};
