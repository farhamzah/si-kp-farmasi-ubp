<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreLecturer extends ReadOnlyCoreModel
{
    protected $table = 'lecturers';

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(CoreUser::class, 'user_id');
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(CoreStudyProgram::class, 'study_program_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(CoreDepartment::class, 'department_id');
    }
}
