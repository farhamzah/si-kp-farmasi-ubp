<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CoreDepartment extends ReadOnlyCoreModel
{
    protected $table = 'departments';

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function studyPrograms(): HasMany
    {
        return $this->hasMany(CoreStudyProgram::class, 'department_id');
    }

    public function lecturers(): HasMany
    {
        return $this->hasMany(CoreLecturer::class, 'department_id');
    }
}
