<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpAssignment extends Model
{
    protected $fillable = [
        'kp_period_id', 'kp_registration_id', 'kp_place_selection_id', 'student_id', 'kp_place_id',
        'internal_supervisor_id', 'field_supervisor_id', 'status', 'assigned_by', 'assigned_at',
        'started_at', 'ended_at', 'active_key', 'note',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'started_at' => 'date',
            'ended_at' => 'date',
        ];
    }

    public function period() { return $this->belongsTo(KpPeriod::class, 'kp_period_id'); }
    public function registration() { return $this->belongsTo(KpRegistration::class, 'kp_registration_id'); }
    public function selection() { return $this->belongsTo(KpPlaceSelection::class, 'kp_place_selection_id'); }
    public function student() { return $this->belongsTo(Student::class); }
    public function place() { return $this->belongsTo(KpPlace::class, 'kp_place_id'); }
    public function internalSupervisor() { return $this->belongsTo(Lecturer::class, 'internal_supervisor_id'); }
    public function fieldSupervisor() { return $this->belongsTo(FieldSupervisor::class, 'field_supervisor_id'); }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by'); }
    public function logs() { return $this->hasMany(KpAssignmentLog::class, 'kp_assignment_id'); }
    public function logbooks() { return $this->hasMany(KpLogbook::class, 'kp_assignment_id'); }
    public function finalReport() { return $this->hasOne(KpFinalReport::class, 'kp_assignment_id'); }
    public function examRequest() { return $this->hasOne(KpExamRequest::class, 'kp_assignment_id'); }
    public function exam() { return $this->hasOne(KpExam::class, 'kp_assignment_id'); }
    public function scores() { return $this->hasMany(KpScore::class, 'kp_assignment_id'); }
    public function finalScore() { return $this->hasOne(KpFinalScore::class, 'kp_assignment_id'); }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'aktif' => 'Aktif',
            'berjalan' => 'Berjalan',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            default => 'Menunggu Pembimbing',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'aktif', 'berjalan' => 'bg-emerald-50 text-emerald-700',
            'selesai' => 'bg-slate-100 text-slate-700',
            'dibatalkan' => 'bg-rose-50 text-rose-700',
            default => 'bg-amber-50 text-amber-700',
        };
    }

    public function isCompleteSupervision(): bool
    {
        return filled($this->internal_supervisor_id) && filled($this->field_supervisor_id);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['aktif', 'berjalan'], true);
    }

    public function canStart(): bool
    {
        return $this->isCompleteSupervision() && $this->status === 'aktif';
    }

    public function supervisorStatusLabel(): string
    {
        if ($this->isCompleteSupervision()) {
            return 'Lengkap';
        }

        if (! $this->internal_supervisor_id && ! $this->field_supervisor_id) {
            return 'Belum ada pembimbing';
        }

        return ! $this->internal_supervisor_id ? 'Belum ada pembimbing dalam' : 'Belum ada pembimbing lapangan';
    }

    public function isEligibleForExamRequest(): bool
    {
        $this->loadMissing('finalReport');

        return $this->isActive() && $this->finalReport?->isApproved();
    }

    public function scoresCompletionPercentage(): int
    {
        $this->loadMissing('scores');
        $components = $this->period?->assessmentComponents()->where('status', 'aktif')->where('is_required', true)->get() ?? collect();

        if ($components->isEmpty()) {
            return 0;
        }

        $submitted = $components->filter(fn ($component) => in_array($this->scores->firstWhere('kp_assessment_component_id', $component->id)?->status, ['submitted', 'locked'], true))->count();

        return (int) round(($submitted / $components->count()) * 100);
    }

    public function isAllRequiredScoresSubmitted(): bool
    {
        $this->loadMissing('scores');
        $components = $this->period?->assessmentComponents()->where('status', 'aktif')->where('is_required', true)->get() ?? collect();

        return $components->isNotEmpty() && $components->every(fn ($component) => in_array($this->scores->firstWhere('kp_assessment_component_id', $component->id)?->status, ['submitted', 'locked'], true));
    }

    public function calculateFinalScore(): float
    {
        return round((float) $this->scores()->whereIn('status', ['submitted', 'locked'])->sum('weighted_score'), 2);
    }
}
