<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nim')->nullable()->unique();
            $table->string('study_program')->nullable();
            $table->unsignedTinyInteger('semester')->nullable();
            $table->string('class_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('gender')->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nidn_nip')->nullable()->unique();
            $table->string('employee_number')->nullable()->unique();
            $table->string('study_program')->nullable();
            $table->string('department')->nullable();
            $table->string('expertise')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('field_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('institution_name')->nullable();
            $table->string('position')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_supervisors');
        Schema::dropIfExists('lecturers');
        Schema::dropIfExists('students');
    }
};
