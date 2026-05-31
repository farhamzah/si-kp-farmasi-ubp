<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kp_external_document_references', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('source_app')->default('kp-farmasi');
            $table->string('external_app')->default('tu-farmasi');
            $table->string('document_type');
            $table->string('service_code');
            $table->string('source_module');
            $table->string('source_reference_type');
            $table->string('source_reference_id');
            $table->string('external_document_id')->nullable();
            $table->string('external_document_number')->nullable();
            $table->string('external_status')->nullable();
            $table->string('reference_url')->nullable();
            $table->string('file_hash')->nullable();
            $table->json('metadata')->nullable();
            $table->json('last_payload_snapshot')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['external_app', 'document_type'], 'kp_ext_doc_refs_app_type_idx');
            $table->index(['source_reference_type', 'source_reference_id'], 'kp_ext_doc_refs_source_idx');
            $table->unique(
                ['external_app', 'document_type', 'source_reference_type', 'source_reference_id'],
                'kp_ext_doc_refs_unique_source'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kp_external_document_references');
    }
};
