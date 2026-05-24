<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreStudent extends ReadOnlyCoreModel
{
    protected $table = 'students';

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'enrolled_at' => 'date',
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
}
