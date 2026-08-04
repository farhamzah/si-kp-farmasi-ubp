<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpScoreVisibilityOverride extends Model
{
    protected $fillable = [
        'kp_period_id',
        'student_id',
        'can_view',
        'note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
        ];
    }

    public function period()
    {
        return $this->belongsTo(KpPeriod::class, 'kp_period_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function statusLabel(): string
    {
        return $this->can_view ? 'Dibolehkan khusus' : 'Ditahan khusus';
    }

    public function statusBadgeClass(): string
    {
        return $this->can_view ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700';
    }
}
