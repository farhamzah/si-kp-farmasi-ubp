<?php

namespace App\Models;

use App\Support\KpScoreCalculator;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;

class KpAssignment extends Model
{
    public const WORKDAY_SENIN_JUMAT = 'senin_jumat';
    public const WORKDAY_SENIN_SABTU = 'senin_sabtu';

    public const WORKDAY_PATTERN_LABELS = [
        self::WORKDAY_SENIN_JUMAT => 'Senin - Jumat',
        self::WORKDAY_SENIN_SABTU => 'Senin - Sabtu',
    ];

    protected $fillable = [
        'kp_period_id', 'kp_registration_id', 'kp_place_selection_id', 'student_id', 'kp_place_id',
        'internal_supervisor_id', 'field_supervisor_id', 'status', 'assigned_by', 'assigned_at',
        'started_at', 'ended_at', 'workday_pattern', 'active_key', 'note', 'integration_revision',
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
    public function reportGuidanceLogs() { return $this->hasMany(KpReportGuidanceLog::class, 'kp_assignment_id'); }
    public function examRequest() { return $this->hasOne(KpExamRequest::class, 'kp_assignment_id'); }
    public function exam() { return $this->hasOne(KpExam::class, 'kp_assignment_id'); }
    public function scores() { return $this->hasMany(KpScore::class, 'kp_assignment_id'); }
    public function finalScore() { return $this->hasOne(KpFinalScore::class, 'kp_assignment_id'); }
    public function competencyAchievements() { return $this->hasMany(KpCompetencyAchievement::class, 'kp_assignment_id'); }

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
        return collect($this->examEligibility()['items'])->every(fn (array $item): bool => $item['ready']);
    }

    public function isReadyForAssessment(string $assessorType): bool
    {
        return $this->assessmentEligibility($assessorType)['ready'];
    }

    public function assessmentEligibility(string $assessorType): array
    {
        $this->loadMissing('finalReport');

        $items = match ($assessorType) {
            'pembimbing_lapangan' => $this->fieldSupervisorAssessmentItems(),
            'pembimbing_dalam' => $this->internalSupervisorAssessmentItems(),
            default => [
                [
                    'key' => 'assessment_available',
                    'label' => 'Penilaian tersedia',
                    'ready' => true,
                    'description' => 'Tidak ada prasyarat tambahan.',
                ],
            ],
        };

        return [
            'ready' => collect($items)->every(fn (array $item): bool => $item['ready']),
            'items' => $items,
            'pending' => collect($items)->filter(fn (array $item): bool => ! $item['ready'])->values()->all(),
        ];
    }

    public function examEligibility(): array
    {
        $this->loadMissing('finalReport');

        $approvedLogbooks = $this->logbooks()->where('status', 'disetujui')->count();
        $openLogbooks = $this->logbooks()->whereIn('status', ['menunggu_validasi', 'revisi', 'ditolak'])->count();
        [$reviewedInternalGuidance, $pendingInternalGuidance] = $this->reportGuidanceCounts(KpReportGuidanceLog::REVIEWER_INTERNAL);
        [$reviewedFieldGuidance, $pendingFieldGuidance] = $this->reportGuidanceCounts(KpReportGuidanceLog::REVIEWER_FIELD);
        $report = $this->finalReport;
        $internalGuidanceCompleted = (bool) $report?->internal_guidance_completed_at;
        $fieldGuidanceCompleted = (bool) $report?->field_guidance_completed_at;
        $internalGuidanceDescription = $internalGuidanceCompleted
            ? $reviewedInternalGuidance.'/8 sesi direview, bimbingan dalam selesai'
            : $reviewedInternalGuidance.'/8 sesi direview, '.$pendingInternalGuidance.' menunggu validasi';
        $fieldGuidanceDescription = $fieldGuidanceCompleted
            ? $reviewedFieldGuidance.' sesi direview, bimbingan lapangan selesai'
            : $reviewedFieldGuidance.' sesi direview, '.$pendingFieldGuidance.' menunggu validasi';

        $items = [
            [
                'key' => 'assignment_active',
                'label' => 'Penempatan KP aktif',
                'ready' => $this->isActive(),
                'description' => $this->statusLabel(),
            ],
            [
                'key' => 'field_logbook_validated',
                'label' => 'Logbook KP tervalidasi pembimbing lapangan',
                'ready' => $approvedLogbooks > 0 && $openLogbooks === 0,
                'description' => $approvedLogbooks.' disetujui, '.$openLogbooks.' perlu tindak lanjut',
            ],
            [
                'key' => 'field_report_guidance_completed',
                'label' => 'Bimbingan laporan pembimbing lapangan selesai',
                'ready' => $fieldGuidanceCompleted && $reviewedFieldGuidance > 0 && $pendingFieldGuidance === 0,
                'description' => $fieldGuidanceDescription,
            ],
            [
                'key' => 'internal_report_guidance_completed',
                'label' => 'Bimbingan laporan pembimbing dalam minimal 8 kali dan selesai',
                'ready' => $internalGuidanceCompleted && $reviewedInternalGuidance >= 8 && $pendingInternalGuidance === 0,
                'description' => $internalGuidanceDescription,
            ],
            [
                'key' => 'final_report_submitted',
                'label' => 'Link/file laporan final tersedia',
                'ready' => $report && ($report->files()->exists() || filled($report->final_document_url)),
                'description' => $report?->latestFile?->original_filename ?? ($report?->final_document_label ?: ($report?->final_document_url ? 'Link final tersedia' : 'Belum tersedia')),
            ],
            [
                'key' => 'internal_report_approved',
                'label' => 'Laporan disetujui pembimbing dalam',
                'ready' => $report?->internal_review_status === 'disetujui',
                'description' => $report?->internalReviewStatusLabel() ?? 'Belum review',
            ],
            [
                'key' => 'field_report_approved',
                'label' => 'Laporan disetujui pembimbing lapangan',
                'ready' => $report?->field_review_status === 'disetujui',
                'description' => $report?->fieldReviewStatusLabel() ?? 'Belum review',
            ],
        ];

        return [
            'ready' => collect($items)->every(fn (array $item): bool => $item['ready']),
            'items' => $items,
        ];
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
        $this->loadMissing(['scores', 'exam.examiners.user', 'exam.examiner.user']);
        $components = $this->period?->assessmentComponents()->where('status', 'aktif')->where('is_required', true)->get() ?? collect();

        return $components->isNotEmpty() && $components->every(function ($component): bool {
            if ($component->assessor_type !== 'penguji') {
                return in_array($this->scores->firstWhere('kp_assessment_component_id', $component->id)?->status, ['submitted', 'locked'], true);
            }

            $examinerUserIds = $this->exam
                ? $this->exam->examiners
                    ->when($this->exam->examiner, fn ($examiners) => $examiners->prepend($this->exam->examiner))
                    ->unique('id')
                    ->pluck('user_id')
                    ->filter()
                    ->values()
                : collect();

            if ($examinerUserIds->isEmpty()) {
                return false;
            }

            return $examinerUserIds->every(fn (int $userId): bool => $this->scores
                ->where('kp_assessment_component_id', $component->id)
                ->where('assessor_user_id', $userId)
                ->whereIn('status', ['submitted', 'locked'])
                ->isNotEmpty());
        });
    }

    public function calculateFinalScore(): float
    {
        return app(KpScoreCalculator::class)->breakdown($this)['final_score'];
    }

    public function workdayPatternLabel(): string
    {
        return self::WORKDAY_PATTERN_LABELS[$this->workday_pattern ?: self::WORKDAY_SENIN_JUMAT] ?? 'Senin - Jumat';
    }

    public function expectedWorkdaysCount(): int
    {
        $start = $this->started_at ?: $this->period?->kp_start_date;
        $end = $this->ended_at ?: $this->period?->kp_end_date;

        if (! $start || ! $end || $end->lt($start)) {
            return 0;
        }

        $days = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            if ($date->isSunday()) {
                continue;
            }

            if (($this->workday_pattern ?: self::WORKDAY_SENIN_JUMAT) === self::WORKDAY_SENIN_JUMAT && $date->isSaturday()) {
                continue;
            }

            $days++;
        }

        return $days;
    }

    private function fieldSupervisorAssessmentItems(): array
    {
        $totalLogbooks = $this->logbooks()->count();
        $approvedLogbooks = $this->logbooks()->where('status', 'disetujui')->count();
        $unfinishedLogbooks = $this->logbooks()->where('status', '!=', 'disetujui')->count();
        [$reviewedFieldGuidance, $pendingFieldGuidance] = $this->reportGuidanceCounts(KpReportGuidanceLog::REVIEWER_FIELD);
        $report = $this->finalReport;
        $fieldGuidanceCompleted = (bool) $report?->field_guidance_completed_at;

        return [
            [
                'key' => 'field_logbook_all_validated',
                'label' => 'Semua logbook KP sudah divalidasi',
                'ready' => $totalLogbooks > 0 && $unfinishedLogbooks === 0,
                'description' => $approvedLogbooks.'/'.$totalLogbooks.' logbook disetujui',
            ],
            [
                'key' => 'field_report_guidance_completed',
                'label' => 'Bimbingan laporan lapangan sudah ditandai selesai',
                'ready' => $fieldGuidanceCompleted && $reviewedFieldGuidance > 0 && $pendingFieldGuidance === 0,
                'description' => $fieldGuidanceCompleted
                    ? $reviewedFieldGuidance.' sesi direview, selesai'
                    : $reviewedFieldGuidance.' sesi direview, '.$pendingFieldGuidance.' menunggu validasi',
            ],
            [
                'key' => 'field_report_approved',
                'label' => 'Laporan akhir sudah disetujui pembimbing lapangan',
                'ready' => $report?->field_review_status === 'disetujui',
                'description' => $report?->fieldReviewStatusLabel() ?? 'Belum review',
            ],
        ];
    }

    private function internalSupervisorAssessmentItems(): array
    {
        [$reviewedInternalGuidance, $pendingInternalGuidance] = $this->reportGuidanceCounts(KpReportGuidanceLog::REVIEWER_INTERNAL);
        $report = $this->finalReport;
        $internalGuidanceCompleted = (bool) $report?->internal_guidance_completed_at;

        return [
            [
                'key' => 'internal_report_guidance_completed',
                'label' => 'Bimbingan laporan pembimbing dalam minimal 8 kali dan selesai',
                'ready' => $internalGuidanceCompleted && $reviewedInternalGuidance >= 8 && $pendingInternalGuidance === 0,
                'description' => $internalGuidanceCompleted
                    ? $reviewedInternalGuidance.'/8 sesi direview, selesai'
                    : $reviewedInternalGuidance.'/8 sesi direview, '.$pendingInternalGuidance.' menunggu validasi',
            ],
            [
                'key' => 'internal_report_approved',
                'label' => 'Laporan akhir sudah disetujui pembimbing dalam',
                'ready' => $report?->internal_review_status === 'disetujui',
                'description' => $report?->internalReviewStatusLabel() ?? 'Belum review',
            ],
        ];
    }

    private function reportGuidanceCounts(string $reviewerType): array
    {
        $query = $this->reportGuidanceLogs();

        if ($reviewerType === KpReportGuidanceLog::REVIEWER_INTERNAL) {
            $query->where(function ($query): void {
                $query->where('reviewer_type', KpReportGuidanceLog::REVIEWER_INTERNAL)
                    ->orWhereNull('reviewer_type');
            });
        } else {
            $query->where('reviewer_type', $reviewerType);
        }

        $reviewed = (clone $query)->whereIn('status', ['disetujui', 'revisi'])->count();
        $pending = (clone $query)->where('status', 'menunggu_validasi')->count();

        return [$reviewed, $pending];
    }
}
