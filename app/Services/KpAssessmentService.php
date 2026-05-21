<?php

namespace App\Services;

use App\Models\KpAssessmentComponent;
use App\Models\KpAssignment;
use App\Models\KpFinalScore;
use App\Models\KpScore;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KpAssessmentService
{
    public function saveScore(User $assessor, KpAssignment $assignment, KpAssessmentComponent $component, float $score, ?string $note = null): KpScore
    {
        $this->ensureCanAssess($assessor, $assignment, $component->assessor_type);
        $this->ensureFinalScoreEditable($assignment);

        if ($component->kp_period_id !== $assignment->kp_period_id || $component->status !== 'aktif') {
            throw ValidationException::withMessages(['component' => 'Komponen penilaian tidak sesuai periode penempatan.']);
        }
        if ($score < 0 || $score > (float) $component->max_score) {
            throw ValidationException::withMessages(['scores' => 'Nilai harus berada pada rentang 0 sampai '.$component->max_score.'.']);
        }

        $exam = $component->assessor_type === 'penguji' ? $assignment->exam : null;
        if ($component->assessor_type === 'penguji' && ! $exam) {
            throw ValidationException::withMessages(['exam' => 'Sidang belum dijadwalkan untuk penilaian penguji.']);
        }

        return DB::transaction(function () use ($assessor, $assignment, $component, $score, $note, $exam) {
            $kpScore = KpScore::updateOrCreate(
                ['kp_assignment_id' => $assignment->id, 'kp_assessment_component_id' => $component->id],
                [
                    'kp_exam_id' => $exam?->id,
                    'assessor_user_id' => $assessor->id,
                    'assessor_type' => $component->assessor_type,
                    'score' => $score,
                    'weighted_score' => round(($score * (float) $component->weight) / 100, 2),
                    'note' => $note,
                    'status' => 'draft',
                    'submitted_at' => null,
                    'locked_at' => null,
                ]
            );

            $this->logActivity($assessor, $assignment, 'score_saved', $kpScore, null, 'draft', $note, ['component_id' => $component->id]);

            return $kpScore;
        });
    }

    public function submitScores(User $assessor, KpAssignment $assignment, string $assessorType): void
    {
        $this->ensureCanAssess($assessor, $assignment, $assessorType);
        $this->ensureFinalScoreEditable($assignment);

        $components = $assignment->period->assessmentComponents()
            ->where('status', 'aktif')
            ->where('assessor_type', $assessorType)
            ->where('is_required', true)
            ->get();

        foreach ($components as $component) {
            if (! $assignment->scores()->where('kp_assessment_component_id', $component->id)->exists()) {
                throw ValidationException::withMessages(['scores' => 'Semua komponen wajib harus diisi sebelum submit.']);
            }
        }

        $assignment->scores()
            ->where('assessor_type', $assessorType)
            ->where('status', 'draft')
            ->each(function (KpScore $score) use ($assessor, $assignment) {
                $old = $score->status;
                $score->update(['status' => 'submitted', 'submitted_at' => now()]);
                $this->logActivity($assessor, $assignment, 'score_submitted', $score, $old, 'submitted', $score->note);
            });
    }

    public function calculateFinalScore(KpAssignment $assignment): KpFinalScore
    {
        $score = $assignment->calculateFinalScore();
        $final = KpFinalScore::updateOrCreate(
            ['kp_assignment_id' => $assignment->id],
            [
                'final_score' => $score,
                'final_grade' => $this->gradeFor($score),
                'status' => 'calculated',
                'calculated_at' => now(),
            ]
        );
        $this->logActivity(auth()->user(), $assignment, 'final_score_calculated', null, null, 'calculated', null, ['final_score' => $score], $final);

        return $final;
    }

    public function finalizeScore(User $actor, KpAssignment $assignment, ?string $note = null): KpFinalScore
    {
        $assignment->loadMissing(['scores', 'period.assessmentComponents']);
        if (! $assignment->isAllRequiredScoresSubmitted()) {
            throw ValidationException::withMessages(['final_score' => 'Nilai belum bisa difinalisasi karena komponen wajib belum lengkap.']);
        }

        $final = $this->calculateFinalScore($assignment);
        $old = $final->status;
        $final->update(['status' => 'locked', 'finalized_by' => $actor->id, 'finalized_at' => now(), 'note' => $note]);
        $assignment->scores()->where('status', 'submitted')->update(['status' => 'locked', 'locked_at' => now()]);
        $this->logActivity($actor, $assignment, 'final_score_finalized', null, $old, 'locked', $note, null, $final->fresh());

        return $final->fresh();
    }

    public function publishScore(User $actor, KpFinalScore $finalScore): KpFinalScore
    {
        $old = $finalScore->status;
        $finalScore->update(['status' => 'published', 'published_at' => now()]);
        $this->logActivity($actor, $finalScore->assignment, 'final_score_published', null, $old, 'published', null, null, $finalScore->fresh());

        return $finalScore->fresh();
    }

    public function unlockScore(User $actor, KpFinalScore $finalScore, string $reason): KpFinalScore
    {
        $old = $finalScore->status;
        $finalScore->update(['status' => 'calculated', 'note' => $reason]);
        $finalScore->assignment->scores()->where('status', 'locked')->update(['status' => 'submitted', 'locked_at' => null]);
        $this->logActivity($actor, $finalScore->assignment, 'final_score_unlocked', null, $old, 'calculated', $reason, null, $finalScore->fresh());

        return $finalScore->fresh();
    }

    public function logActivity(?User $user, KpAssignment $assignment, string $action, ?KpScore $score = null, ?string $oldStatus = null, ?string $newStatus = null, ?string $note = null, ?array $metadata = null, ?KpFinalScore $finalScore = null): void
    {
        \App\Models\KpScoreLog::create([
            'kp_assignment_id' => $assignment->id,
            'kp_score_id' => $score?->id,
            'kp_final_score_id' => $finalScore?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $note,
            'metadata' => $metadata,
        ]);
    }

    private function ensureCanAssess(User $assessor, KpAssignment $assignment, string $assessorType): void
    {
        $assignment->loadMissing(['internalSupervisor.user', 'fieldSupervisor.user', 'exam.examiner.user']);

        $allowed = match ($assessorType) {
            'pembimbing_dalam' => $assessor->lecturer && $assignment->internal_supervisor_id === $assessor->lecturer->id,
            'pembimbing_lapangan' => $assessor->fieldSupervisor && $assignment->field_supervisor_id === $assessor->fieldSupervisor->id,
            'penguji' => $assessor->lecturer && $assignment->exam?->examiner_id === $assessor->lecturer->id,
            default => false,
        };

        abort_unless($allowed, 403, 'Anda tidak berhak menginput nilai penempatan ini.');
    }

    private function ensureFinalScoreEditable(KpAssignment $assignment): void
    {
        $final = $assignment->finalScore;
        if ($final?->isLocked()) {
            throw ValidationException::withMessages(['final_score' => 'Nilai sudah dikunci/dipublikasikan dan tidak bisa diubah.']);
        }
    }

    private function gradeFor(float $score): string
    {
        return match (true) {
            $score >= 85 => 'A',
            $score >= 75 => 'B',
            $score >= 65 => 'C',
            $score >= 50 => 'D',
            default => 'E',
        };
    }
}
