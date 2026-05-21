<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    protected $fillable = [
        'user_id',
        'nidn_nip',
        'employee_number',
        'study_program',
        'department',
        'expertise',
        'phone',
        'address',
        'status',
        'profile_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'profile_completed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function internalAssignments()
    {
        return $this->hasMany(KpAssignment::class, 'internal_supervisor_id');
    }

    public function supervisedLogbooks()
    {
        return $this->hasManyThrough(KpLogbook::class, KpAssignment::class, 'internal_supervisor_id', 'kp_assignment_id');
    }
}
