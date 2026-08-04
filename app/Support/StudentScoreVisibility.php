<?php

namespace App\Support;

use App\Models\KpAssignment;
use App\Models\KpQuestionnaire;
use App\Models\KpQuestionnaireResponse;
use App\Models\KpScoreVisibilityOverride;

class StudentScoreVisibility
{
    public function resolve(KpAssignment $assignment): array
    {
        $assignment->loadMissing(['period', 'student.user', 'finalScore', 'finalReport']);

        $override = KpScoreVisibilityOverride::query()
            ->where('kp_period_id', $assignment->kp_period_id)
            ->where('student_id', $assignment->student_id)
            ->first();

        $requirements = $this->requirements($assignment);

        if (! $assignment->finalScore) {
            return $this->result(false, 'not_calculated', $override, $requirements, 'Nilai akhir belum dihitung.');
        }

        if (! $assignment->finalScore->isVisibleToStudent()) {
            return $this->result(false, 'not_published', $override, $requirements, 'Nilai akhir belum dipublish oleh Koordinator KP.');
        }

        if ($override && ! $override->can_view) {
            return $this->result(false, 'student_blocked', $override, $requirements, 'Akses nilai Anda ditahan oleh Koordinator KP.');
        }

        if (! $assignment->period?->score_visible_to_students && ! ($override?->can_view)) {
            return $this->result(false, 'period_closed', $override, $requirements, 'Akses nilai periode ini belum dibuka oleh Koordinator KP.');
        }

        if (! collect($requirements)->every(fn (array $item): bool => $item['ready'])) {
            return $this->result(false, 'requirements_incomplete', $override, $requirements, 'Nilai belum bisa dibuka karena syarat akhir belum lengkap.');
        }

        return $this->result(true, 'open', $override, $requirements, 'Nilai akhir dapat dilihat.');
    }

    public function requirements(KpAssignment $assignment): array
    {
        $assignment->loadMissing(['finalReport', 'student.user']);

        $report = $assignment->finalReport;
        $hasFinalDocument = $report ? ($report->files()->exists() || filled($report->final_document_url)) : false;
        $reportApproved = (bool) ($report?->isApproved() && $hasFinalDocument);
        $studentQuestionnaireSubmitted = $this->studentQuestionnaireSubmitted($assignment);

        return [
            [
                'key' => 'final_report_approved',
                'label' => 'Laporan final sudah tersedia dan disetujui pembimbing',
                'ready' => $reportApproved,
                'description' => $reportApproved
                    ? 'Dokumen final tersedia dan review pembimbing lengkap.'
                    : 'Upload/link laporan final harus tersedia dan disetujui pembimbing dalam serta lapangan.',
            ],
            [
                'key' => 'student_questionnaire_submitted',
                'label' => 'Kuisioner KP mahasiswa sudah diisi',
                'ready' => $studentQuestionnaireSubmitted,
                'description' => $studentQuestionnaireSubmitted
                    ? 'Kuisioner mahasiswa sudah masuk.'
                    : 'Mahasiswa perlu mengisi kuisioner KP terlebih dahulu.',
            ],
        ];
    }

    private function studentQuestionnaireSubmitted(KpAssignment $assignment): bool
    {
        $hasActiveStudentQuestionnaire = KpQuestionnaire::query()
            ->active()
            ->where('audience', KpQuestionnaire::AUDIENCE_STUDENT)
            ->where(function ($query) use ($assignment): void {
                $query->whereNull('kp_period_id')
                    ->orWhere('kp_period_id', $assignment->kp_period_id);
            })
            ->exists();

        if (! $hasActiveStudentQuestionnaire) {
            return true;
        }

        return KpQuestionnaireResponse::query()
            ->where('kp_assignment_id', $assignment->id)
            ->where('respondent_user_id', $assignment->student?->user_id)
            ->where('status', 'submitted')
            ->whereHas('questionnaire', fn ($query) => $query->where('audience', KpQuestionnaire::AUDIENCE_STUDENT))
            ->exists();
    }

    private function result(bool $visible, string $reason, ?KpScoreVisibilityOverride $override, array $requirements, string $message): array
    {
        return [
            'visible' => $visible,
            'reason' => $reason,
            'override' => $override,
            'requirements' => $requirements,
            'message' => $message,
        ];
    }
}
