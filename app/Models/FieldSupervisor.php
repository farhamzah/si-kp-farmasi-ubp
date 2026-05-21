<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldSupervisor extends Model
{
    protected $fillable = [
        'user_id',
        'institution_name',
        'position',
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
}
