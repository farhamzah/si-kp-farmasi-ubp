<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpPlaceSelection extends Model
{
    protected $fillable = [
        'kp_period_id',
        'kp_registration_id',
        'student_id',
        'kp_place_id',
        'kp_place_quota_id',
        'selected_at',
        'selected_by',
        'status',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'moved_from_selection_id',
        'active_key',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'selected_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function selectedBy()
    {
        return $this->belongsTo(User::class, 'selected_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function movedFromSelection()
    {
        return $this->belongsTo(self::class, 'moved_from_selection_id');
    }

    public function assignment()
    {
        return $this->hasOne(KpAssignment::class, 'kp_place_selection_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'dibatalkan' => 'Dibatalkan',
            'dipindahkan' => 'Dipindahkan',
            default => 'Aktif',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'dibatalkan' => 'bg-rose-50 text-rose-700',
            'dipindahkan' => 'bg-amber-50 text-amber-700',
            default => 'bg-emerald-50 text-emerald-700',
        };
    }
}
