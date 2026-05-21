<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpRegistrationLog extends Model
{
    protected $fillable = [
        'kp_registration_id',
        'user_id',
        'action',
        'old_status',
        'new_status',
        'note',
    ];

    public function registration()
    {
        return $this->belongsTo(KpRegistration::class, 'kp_registration_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
