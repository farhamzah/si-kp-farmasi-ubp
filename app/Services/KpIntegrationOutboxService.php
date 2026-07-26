<?php

namespace App\Services;

use App\Jobs\DeliverIntegrationOutboxEvent;
use App\Models\IntegrationOutboxEvent;
use App\Models\KpAssignment;
use App\Models\KpExam;
use App\Models\Lecturer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KpIntegrationOutboxService
{
    public function __construct(private readonly KpDosenPortfolioEventFactory $factory) {}

    public function enqueueSupervisorAssigned(KpAssignment $assignment, Lecturer $lecturer): void
    {
        $assignment->loadMissing(['student.user', 'period']);
        $this->storeAndDispatch([$this->factory->supervisorAssigned($assignment, $lecturer)]);
    }

    public function enqueueSupervisorChanged(KpAssignment $assignment, Lecturer $oldLecturer, Lecturer $newLecturer, ?string $reason = null): void
    {
        $assignment->loadMissing(['student.user', 'period']);
        $this->storeAndDispatch([$this->factory->supervisorChanged($assignment, $oldLecturer, $newLecturer, $reason)]);
    }

    public function enqueueExamScheduled(KpExam $exam): void
    {
        $exam->loadMissing(['assignment.student.user', 'assignment.period', 'supervisor', 'examiners']);
        $events = [];
        $events[] = $this->factory->examScheduled($exam, $exam->supervisor, 'PEMBIMBING_DALAM');

        foreach ($this->examinerLecturers($exam) as $index => $lecturer) {
            $events[] = $this->factory->examinerAssigned($exam, $lecturer, $index);
            $events[] = $this->factory->examScheduled($exam, $lecturer, 'PENGUJI_'.($index + 1));
        }

        $this->storeAndDispatch($events);
    }

    public function enqueueExamRescheduled(KpExam $exam, array $oldExaminerIds, ?string $reason = null): void
    {
        $exam->loadMissing(['assignment.student.user', 'assignment.period', 'supervisor', 'examiners']);
        $events = [];
        $events[] = $this->factory->examRescheduled($exam, $exam->supervisor, 'PEMBIMBING_DALAM');
        $newExaminers = $this->examinerLecturers($exam);
        $oldExaminers = Lecturer::query()->whereIn('id', $oldExaminerIds)->get()->keyBy('id');

        foreach ($newExaminers as $index => $lecturer) {
            $oldId = $oldExaminerIds[$index] ?? null;
            if ($oldId && (int) $oldId !== (int) $lecturer->id && $oldExaminers->has($oldId)) {
                $events[] = $this->factory->examinerChanged($exam, $oldExaminers->get($oldId), $lecturer, $index, $reason);
            }

            $events[] = $this->factory->examRescheduled($exam, $lecturer, 'PENGUJI_'.($index + 1));
        }

        $this->storeAndDispatch($events);
    }

    public function enqueueExamCompleted(KpExam $exam): void
    {
        $exam->loadMissing(['assignment.student.user', 'assignment.period', 'supervisor', 'examiners']);
        $events = [$this->factory->examCompleted($exam, $exam->supervisor, 'PEMBIMBING_DALAM')];

        foreach ($this->examinerLecturers($exam) as $index => $lecturer) {
            $events[] = $this->factory->examCompleted($exam, $lecturer, 'PENGUJI_'.($index + 1));
        }

        $this->storeAndDispatch($events);
    }

    public function enqueueExamCancelled(KpExam $exam, ?string $reason): void
    {
        $exam->loadMissing(['assignment.student.user', 'assignment.period', 'supervisor', 'examiners']);
        $events = [$this->factory->examCancelled($exam, $exam->supervisor, 'PEMBIMBING_DALAM', $reason)];

        foreach ($this->examinerLecturers($exam) as $index => $lecturer) {
            $events[] = $this->factory->examCancelled($exam, $lecturer, 'PENGUJI_'.($index + 1), $reason);
        }

        $this->storeAndDispatch($events);
    }

    private function storeAndDispatch(array $eventPayloads): void
    {
        $events = collect($eventPayloads)->map(fn (array $payload) => IntegrationOutboxEvent::query()->create($payload));

        if ((bool) config('dosen_farmasi.integration.enabled')) {
            DB::afterCommit(function () use ($events): void {
                $events->each(fn (IntegrationOutboxEvent $event) => DeliverIntegrationOutboxEvent::dispatch($event->id)->afterCommit());
            });
        }
    }

    private function examinerLecturers(KpExam $exam): Collection
    {
        return $exam->examiners
            ->when($exam->examiner, fn (Collection $examiners) => $examiners->prepend($exam->examiner))
            ->unique('id')
            ->values();
    }
}
