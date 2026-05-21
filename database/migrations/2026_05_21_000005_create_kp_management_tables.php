<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kp_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('academic_year')->nullable();
            $table->enum('semester', ['ganjil', 'genap', 'antara'])->nullable();
            $table->dateTime('registration_start_at')->nullable();
            $table->dateTime('registration_end_at')->nullable();
            $table->dateTime('document_verification_start_at')->nullable();
            $table->dateTime('document_verification_end_at')->nullable();
            $table->dateTime('selection_start_at')->nullable();
            $table->dateTime('selection_end_at')->nullable();
            $table->date('kp_start_date')->nullable();
            $table->date('kp_end_date')->nullable();
            $table->enum('status', ['draft', 'dibuka', 'ditutup', 'selesai'])->default('draft');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kp_places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['apotek', 'rumah_sakit', 'puskesmas', 'industri', 'klinik', 'distributor', 'lainnya']);
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kp_place_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_period_id')->constrained('kp_periods')->cascadeOnDelete();
            $table->foreignId('kp_place_id')->constrained('kp_places')->cascadeOnDelete();
            $table->unsignedInteger('quota')->default(0);
            $table->boolean('is_open')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['kp_period_id', 'kp_place_id']);
        });

        Schema::create('kp_quota_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_place_quota_id')->constrained('kp_place_quotas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('old_quota')->nullable();
            $table->unsignedInteger('new_quota')->nullable();
            $table->boolean('old_is_open')->nullable();
            $table->boolean('new_is_open')->nullable();
            $table->enum('action', ['created', 'updated', 'opened', 'closed', 'quota_increased', 'quota_decreased', 'deleted']);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kp_quota_logs');
        Schema::dropIfExists('kp_place_quotas');
        Schema::dropIfExists('kp_places');
        Schema::dropIfExists('kp_periods');
    }
};
