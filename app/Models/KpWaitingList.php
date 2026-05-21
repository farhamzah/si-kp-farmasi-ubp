<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpWaitingList extends Model
{
    protected $fillable = [
        'kp_period_id',
        'kp_registration_id',
        'student_id',
        'joined_at',
        'status',
        'notified_at',
        'resolved_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'notified_at' => 'datetime',
            'resolved_at' => 'datetime',
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

    public function statusLabel(): string
    {
        return match ($this->status) {
            'sudah_memilih' => 'Sudah Memilih',
            'dibatalkan' => 'Dibatalkan',
            default => 'Menunggu',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'sudah_memilih' => 'bg-emerald-50 text-emerald-700',
            'dibatalkan' => 'bg-rose-50 text-rose-700',
            default => 'bg-amber-50 text-amber-700',
        };
    }
}
