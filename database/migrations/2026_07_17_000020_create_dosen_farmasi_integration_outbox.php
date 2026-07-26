<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kp_assignments', function (Blueprint $table): void {
            if (! Schema::hasColumn('kp_assignments', 'integration_revision')) {
                $table->unsignedInteger('integration_revision')->default(0)->after('note');
            }
        });

        Schema::table('kp_exams', function (Blueprint $table): void {
            if (! Schema::hasColumn('kp_exams', 'integration_revision')) {
                $table->unsignedInteger('integration_revision')->default(0)->after('note');
            }
        });

        Schema::create('integration_outbox_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->string('destination_app')->default('dosen-farmasi')->index();
            $table->string('event_type')->index();
            $table->unsignedSmallInteger('event_version')->default(1);
            $table->string('source_app')->default('kp-farmasi');
            $table->string('source_record_id')->index();
            $table->unsignedInteger('source_revision');
            $table->uuid('correlation_id')->nullable();
            $table->json('payload');
            $table->enum('status', ['PENDING', 'PROCESSING', 'SENT', 'FAILED', 'CANCELLED'])->default('PENDING')->index();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('available_at')->nullable()->index();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedSmallInteger('last_http_status')->nullable();
            $table->string('last_error_code')->nullable();
            $table->string('last_error_message', 1000)->nullable();
            $table->timestamps();

            $table->index(['status', 'available_at'], 'integration_outbox_status_available_idx');
            $table->index(['destination_app', 'status'], 'integration_outbox_destination_status_idx');
            $table->index(['event_type', 'created_at'], 'integration_outbox_event_created_idx');
            $table->index(['source_record_id', 'event_type'], 'integration_outbox_record_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_outbox_events');

        Schema::table('kp_exams', function (Blueprint $table): void {
            if (Schema::hasColumn('kp_exams', 'integration_revision')) {
                $table->dropColumn('integration_revision');
            }
        });

        Schema::table('kp_assignments', function (Blueprint $table): void {
            if (Schema::hasColumn('kp_assignments', 'integration_revision')) {
                $table->dropColumn('integration_revision');
            }
        });
    }
};
