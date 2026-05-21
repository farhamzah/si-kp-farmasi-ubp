<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpSelectionLog extends Model
{
    protected $fillable = [
        'kp_period_id',
        'kp_registration_id',
        'student_id',
        'kp_place_id',
        'kp_place_quota_id',
        'user_id',
        'action',
        'status',
        'message',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function period()
    {
        return $this->belongsTo(KpPeriod::class, 'kp_period_id');
    }

    public function registration()
    {
        return $this->belongsTo(KpRegistration::class, 'kp_registration_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function place()
    {
        return $this->belongsTo(KpPlace::class, 'kp_place_id');
    }

    public function quota()
    {
        return $this->belongsTo(KpPlaceQuota::class, 'kp_place_quota_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
