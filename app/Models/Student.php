<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'nim',
        'study_program',
        'semester',
        'class_name',
        'phone',
        'address',
        'gender',
        'birth_place',
        'birth_date',
        'status',
        'profile_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'profile_completed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
