<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kp_exam_requests', function (Blueprint $table): void {
            $table->string('payment_proof_status')->nullable()->after('payment_proof_size');
            $table->text('payment_proof_review_note')->nullable()->after('payment_proof_status');
            $table->foreignId('payment_proof_reviewed_by')->nullable()->after('payment_proof_review_note')->constrained('users')->nullOnDelete();
            $table->timestamp('payment_proof_reviewed_at')->nullable()->after('payment_proof_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('kp_exam_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('payment_proof_reviewed_by');
            $table->dropColumn([
                'payment_proof_status',
                'payment_proof_review_note',
                'payment_proof_reviewed_at',
            ]);
        });
    }
};
