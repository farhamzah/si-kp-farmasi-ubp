<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kp_exams', function (Blueprint $table) {
            $table->foreignId('chair_lecturer_id')->nullable()->after('examiner_id')->constrained('lecturers')->nullOnDelete();
            $table->unsignedInteger('minutes_sequence')->nullable()->after('chair_lecturer_id');
            $table->string('minutes_number')->nullable()->unique()->after('minutes_sequence');
        });

        Schema::create('kp_exam_minutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kp_exam_id')->unique()->constrained('kp_exams')->cascadeOnDelete();
            $table->string('minutes_number')->unique();
            $table->foreignId('chair_lecturer_id')->nullable()->constrained('lecturers')->nullOnDelete();
            $table->enum('status', ['menunggu_nilai', 'siap_terbit', 'terbit'])->default('menunggu_nilai');
            $table->enum('result', ['lulus', 'lulus_revisi', 'belum_lulus']);
            $table->time('actual_start_time')->nullable();
            $table->time('actual_end_time')->nullable();
            $table->date('revision_deadline')->nullable();
            $table->text('notes')->nullable();
            $table->json('attendance')->nullable();
            $table->string('verification_code', 32)->unique();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });

        $sequences = [];
        DB::table('kp_exams')->orderBy('exam_date')->orderBy('start_time')->orderBy('id')->get()
            ->each(function ($exam) use (&$sequences): void {
                $year = substr((string) $exam->exam_date, 0, 4);
                $month = (int) substr((string) $exam->exam_date, 5, 2);
                $sequences[$year] = ($sequences[$year] ?? 0) + 1;
                $roman = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$month] ?? 'I';

                DB::table('kp_exams')->where('id', $exam->id)->update([
                    'chair_lecturer_id' => $exam->examiner_id,
                    'minutes_sequence' => $sequences[$year],
                    'minutes_number' => str_pad((string) $sequences[$year], 3, '0', STR_PAD_LEFT).'/BA-SKP/FF-UBP/'.$roman.'/'.$year,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('kp_exam_minutes');
        Schema::table('kp_exams', function (Blueprint $table) {
            $table->dropForeign(['chair_lecturer_id']);
            $table->dropUnique(['minutes_number']);
            $table->dropColumn(['chair_lecturer_id', 'minutes_sequence', 'minutes_number']);
        });
    }
};
