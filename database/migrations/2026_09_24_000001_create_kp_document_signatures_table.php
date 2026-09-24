<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kp_document_signatures', function (Blueprint $table): void {
            $table->id();
            $table->string('document_type', 40);
            $table->unsignedBigInteger('document_id');
            $table->unsignedInteger('version')->default(1);
            $table->string('signer_key', 80);
            $table->foreignId('signer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signer_name');
            $table->string('signer_identifier', 100)->nullable();
            $table->string('role_label');
            $table->string('verification_code', 64)->unique();
            $table->string('status', 20)->default('active');
            $table->timestamp('signed_at');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revocation_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'document_id', 'status'], 'kp_document_signatures_document_idx');
            $table->index(['document_type', 'document_id', 'version'], 'kp_document_signatures_version_idx');
        });

        Schema::table('kp_exam_invitations', function (Blueprint $table): void {
            $table->unsignedInteger('document_version')->default(1)->after('status');
            $table->text('last_rebuild_reason')->nullable()->after('document_version');
            $table->foreignId('rebuilt_by')->nullable()->after('last_rebuild_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('rebuilt_at')->nullable()->after('rebuilt_by');
        });

        Schema::table('kp_exam_minutes', function (Blueprint $table): void {
            $table->unsignedInteger('document_version')->default(1)->after('status');
            $table->text('last_rebuild_reason')->nullable()->after('document_version');
            $table->foreignId('rebuilt_by')->nullable()->after('last_rebuild_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('rebuilt_at')->nullable()->after('rebuilt_by');
        });
    }

    public function down(): void
    {
        Schema::table('kp_exam_minutes', function (Blueprint $table): void {
            $table->dropForeign(['rebuilt_by']);
            $table->dropColumn(['document_version', 'last_rebuild_reason', 'rebuilt_by', 'rebuilt_at']);
        });
        Schema::table('kp_exam_invitations', function (Blueprint $table): void {
            $table->dropForeign(['rebuilt_by']);
            $table->dropColumn(['document_version', 'last_rebuild_reason', 'rebuilt_by', 'rebuilt_at']);
        });
        Schema::dropIfExists('kp_document_signatures');
    }
};
