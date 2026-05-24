<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoreStudyProgram extends ReadOnlyCoreModel
{
    protected $table = 'study_programs';

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(CoreDepartment::class, 'department_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(CoreStudent::class, 'study_program_id');
    }

    public function lecturers(): HasMany
    {
        return $this->hasMany(CoreLecturer::class, 'study_program_id');
    }
}
