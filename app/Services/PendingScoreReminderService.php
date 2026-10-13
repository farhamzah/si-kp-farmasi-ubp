<?php

namespace App\Services;

use App\Mail\PendingScoreReminderMail;
use App\Models\KpAssignment;
use App\Models\KpExam;
use App\Models\KpPeriod;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class PendingScoreReminderService
{
    public function pendingForPeriod(KpPeriod $period): Collection
    {
        $componentsByType = $period->assessmentComponents()
            ->where('status', 'aktif')
            ->where('is_required', true)
            ->get()
            ->groupBy('assessor_type');

        if ($componentsByType->isEmpty()) {
            return collect();
        }

        $assignments = KpAssignment::query()
            ->with([
                'student.user', 'place', 'period', 'finalReport', 'reportGuidanceLogs',
                'internalSupervisor.user', 'fieldSupervisor.user', 'exam.examiner.user',
                'exam.examiners.user', 'scores', 'finalScore',
            ])
            ->where('kp_period_id', $period->id)
            ->whereIn('status', ['aktif', 'berjalan', 'selesai'])
            ->get();

        $pending = collect();

        foreach ($assignments as $assignment) {
            if ($assignment->finalScore?->isLocked()) {
                continue;
            }

            $this->appendSupervisor($pending, $assignment, $assignment->internalSupervisor?->user, 'pembimbing_dalam', $componentsByType->get('pembimbing_dalam', collect()));
            $this->appendSupervisor($pending, $assignment, $assignment->fieldSupervisor?->user, 'pembimbing_lapangan', $componentsByType->get('pembimbing_lapangan', collect()));

            $exam = $assignment->exam;
            $examComponents = $componentsByType->get('penguji', collect());
            if (! $exam || $examComponents->isEmpty() || in_array($exam->status, ['dibatalkan', 'ditunda'], true)
                || ($exam->status !== 'selesai' && $exam->exam_date?->isFuture())) {
                continue;
            }

            $examiners = $exam->examiners
                ->when($exam->examiner, fn (Collection $items) => $items->prepend($exam->examiner))
                ->unique('user_id')
                ->filter(fn ($lecturer) => $lecturer?->user);

            foreach ($examiners as $examiner) {
                $this->append($pending, $assignment, $examiner->user, 'penguji', $examComponents, route('examiner.assessments.show', $exam));
            }
        }

        return $pending->values()
            ->map(function (array $row): array {
                $row['items'] = $row['items']->sortBy('student_name')->values();
                $row['pending_count'] = $row['items']->count();

                return $row;
            })
            ->sortBy(fn (array $row): string => $row['assessor']->name.'-'.$row['assessor_type'])
            ->values();
    }

    public function findPending(KpPeriod $period, User $assessor, string $assessorType): ?array
    {
        return $this->pendingForPeriod($period)->first(
            fn (array $row): bool => $row['assessor']->is($assessor) && $row['assessor_type'] === $assessorType
        );
    }

    public function filterPending(Collection $pending, ?string $assessorType = null, ?string $examStage = null, ?string $keyword = null): Collection
    {
        $keyword = str($keyword)->squish()->lower()->toString();

        return $pending
            ->filter(fn (array $row): bool => ! $assessorType || $row['assessor_type'] === $assessorType)
            ->map(function (array $row) use ($examStage, $keyword): array {
                $assessorMatches = $keyword !== '' && str($row['assessor']->name.' '.$row['assessor']->email)->lower()->contains($keyword);
                $row['items'] = $row['items']->filter(function (array $item) use ($examStage, $keyword, $assessorMatches): bool {
                    return (! $examStage || $item['exam_stage'] === $examStage)
                        && ($keyword === '' || $assessorMatches || str($item['student_name'].' '.$item['student_number'].' '.$item['place_name'])->lower()->contains($keyword));
                })->values();
                $row['pending_count'] = $row['items']->count();

                return $row;
            })
            ->filter(fn (array $row): bool => $row['pending_count'] > 0)
            ->values();
    }

    public function send(array $pendingRow, KpPeriod $period): void
    {
        Mail::to($pendingRow['assessor']->email)->send(new PendingScoreReminderMail($pendingRow, $period));
    }

    private function appendSupervisor(Collection $pending, KpAssignment $assignment, ?User $assessor, string $type, Collection $components): void
    {
        if (! $assignment->isReadyForAssessment($type)) {
            return;
        }

        $route = $type === 'pembimbing_dalam'
            ? route('internal-supervisor.assessments.show', $assignment)
            : route('field-supervisor.assessments.show', $assignment);

        $this->append($pending, $assignment, $assessor, $type, $components, $route);
    }

    private function append(Collection $pending, KpAssignment $assignment, ?User $assessor, string $type, Collection $components, string $url): void
    {
        if (! $assessor || blank($assessor->email) || $components->isEmpty()) {
            return;
        }

        $submittedComponentIds = $assignment->scores
            ->where('assessor_user_id', $assessor->id)
            ->where('assessor_type', $type)
            ->whereIn('status', ['submitted', 'locked'])
            ->pluck('kp_assessment_component_id')
            ->unique();

        if ($components->pluck('id')->every(fn (int $id): bool => $submittedComponentIds->contains($id))) {
            return;
        }

        $key = $assessor->id.'-'.$type;
        if (! $pending->has($key)) {
            $pending->put($key, [
                'assessor' => $assessor,
                'assessor_type' => $type,
                'assessor_label' => match ($type) {
                    'pembimbing_dalam' => 'Pembimbing Dalam',
                    'pembimbing_lapangan' => 'Pembimbing Lapangan',
                    default => 'Penguji',
                },
                'items' => collect(),
            ]);
        }

        $row = $pending->get($key);
        $row['items']->push([
            'assignment_id' => $assignment->id,
            'student_name' => $assignment->student?->user?->name ?? '-',
            'student_number' => $assignment->student?->nim ?? '-',
            'place_name' => $assignment->place?->name ?? '-',
            ...$this->examStage($assignment->exam),
            'url' => $url,
        ]);
        $pending->put($key, $row);
    }

    private function examStage(?KpExam $exam): array
    {
        if (! $exam || $exam->status === 'dibatalkan') {
            return ['exam_stage' => 'unscheduled', 'exam_stage_label' => 'Belum dijadwalkan'];
        }
        if ($exam->status === 'selesai') {
            return ['exam_stage' => 'completed', 'exam_stage_label' => 'Sidang selesai'];
        }
        if ($exam->status === 'ditunda') {
            return ['exam_stage' => 'postponed', 'exam_stage_label' => 'Sidang ditunda'];
        }
        if (! $exam->exam_date) {
            return ['exam_stage' => 'unscheduled', 'exam_stage_label' => 'Belum dijadwalkan'];
        }

        $end = $exam->exam_date
            ? Carbon::parse($exam->exam_date->format('Y-m-d').' '.($exam->end_time ?: '23:59:59'), config('app.timezone'))
            : null;

        return $end?->isPast()
            ? ['exam_stage' => 'elapsed', 'exam_stage_label' => 'Jadwal lewat, belum ditandai selesai']
            : ['exam_stage' => 'scheduled', 'exam_stage_label' => 'Akan sidang'];
    }
}
