<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('kp_exams')
            ->whereColumn('chair_lecturer_id', 'examiner_id')
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('kp_exam_minutes')
                ->whereColumn('kp_exam_minutes.kp_exam_id', 'kp_exams.id'))
            ->update(['chair_lecturer_id' => DB::raw('supervisor_id')]);
    }

    public function down(): void
    {
        DB::table('kp_exams')
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('kp_exam_minutes')
                ->whereColumn('kp_exam_minutes.kp_exam_id', 'kp_exams.id'))
            ->update(['chair_lecturer_id' => DB::raw('examiner_id')]);
    }
};
