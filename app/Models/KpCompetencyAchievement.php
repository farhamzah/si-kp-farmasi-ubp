<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpCompetencyAchievement extends Model
{
    protected $fillable = [
        'kp_assignment_id',
        'kp_competency_id',
        'checked_by',
        'achieved_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'achieved_at' => 'datetime',
        ];
    }

    public function assignment()
    {
        return $this->belongsTo(KpAssignment::class, 'kp_assignment_id');
    }

    public function competency()
    {
        return $this->belongsTo(KpCompetency::class, 'kp_competency_id');
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
