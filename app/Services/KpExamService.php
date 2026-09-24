<?php

namespace App\Services;

use App\Models\KpAssignment;
use App\Models\KpExam;
use App\Models\KpExamRequest;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class KpExamService
{
    public function __construct(private readonly KpIntegrationOutboxService $outbox) {}

    public function submitRequest(User $studentUser, KpAssignment $assignment, ?string $note = null, array $paymentProof = []): KpExamRequest
    {
        $this->ensureStudentOwnsAssignment($studentUser, $assignment);
        return $this->createRequest($studentUser, $assignment, $note, $paymentProof);
    }

    public function submitForCoordinator(User $actor, KpAssignment $assignment): KpExamRequest
    {
        abort_unless($actor->hasRole('admin') || $actor->hasRole('koordinator_kp'), 403);

        return $this->createRequest($actor, $assignment, 'Dimasukkan ke antrean sidang oleh koordinator.');
    }

    private function createRequest(User $actor, KpAssignment $assignment, ?string $note = null, array $paymentProof = []): KpExamRequest
    {
        return DB::transaction(function () use ($actor, $assignment, $note, $paymentProof) {
            $assignment = KpAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            if ($assignment->examRequest()->exists()) {
                throw ValidationException::withMessages(['exam' => 'Pengajuan sidang untuk penempatan ini sudah ada.']);
            }
            if (! $assignment->isEligibleForExamRequest()) {
                $pending = collect($assignment->examEligibility()['items'])->first(fn (array $item): bool => $item['required_for_scheduling'] && ! $item['ready']);
                throw ValidationException::withMessages([
                    'exam' => 'Pengajuan sidang belum bisa dilakukan. Lengkapi: '.($pending['label'] ?? 'syarat sidang').'.',
                ]);
            }

            $request = KpExamRequest::create([
                'kp_assignment_id' => $assignment->id,
                'requested_by' => $actor->id,
                'status' => 'diajukan',
                'request_note' => $note,
                ...$this->paymentProofPayload($paymentProof),
                'submitted_at' => now(),
            ]);

            $this->logActivity($actor, $request, null, 'request_submitted', null, 'diajukan', $note);

            return $request;
        });
    }

    public function approveRequest(User $actor, KpExamRequest $request, ?string $note = null): KpExamRequest
    {
        $this->ensureRequestEligibleForScheduling($request);

        $old = $request->status;
        $request->update(['status' => 'disetujui', 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_note' => $note]);
        $this->logActivity($actor, $request->fresh(), null, 'request_approved', $old, 'disetujui', $note);

        return $request->fresh();
    }

    public function approvePaymentProof(User $actor, KpExamRequest $request, ?string $note = null): KpExamRequest
    {
        if (! $request->hasPaymentProof()) {
            throw ValidationException::withMessages(['payment_proof' => 'Bukti pembayaran belum dilampirkan mahasiswa.']);
        }
        if (! $request->isActive()) {
            throw ValidationException::withMessages(['payment_proof' => 'Bukti pembayaran pada status pengajuan ini tidak bisa divalidasi.']);
        }

        $old = $request->paymentProofStatus();
        $request->update([
            'payment_proof_status' => KpExamRequest::PAYMENT_PROOF_APPROVED,
            'payment_proof_review_note' => $note,
            'payment_proof_reviewed_by' => $actor->id,
            'payment_proof_reviewed_at' => now(),
        ]);
        $this->logActivity($actor, $request->fresh(), null, 'payment_proof_approved', $old, KpExamRequest::PAYMENT_PROOF_APPROVED, $note);

        return $request->fresh();
    }

    public function requestPaymentProofRevision(User $actor, KpExamRequest $request, string $note): KpExamRequest
    {
        if (! $request->hasPaymentProof()) {
            throw ValidationException::withMessages(['payment_proof' => 'Bukti pembayaran belum dilampirkan mahasiswa.']);
        }
        if (! $request->isActive()) {
            throw ValidationException::withMessages(['payment_proof' => 'Bukti pembayaran pada status pengajuan ini tidak bisa dikembalikan.']);
        }

        return DB::transaction(function () use ($actor, $request, $note) {
            $oldProofStatus = $request->paymentProofStatus();
            $request->update([
                'payment_proof_status' => KpExamRequest::PAYMENT_PROOF_REVISION,
                'payment_proof_review_note' => $note,
                'payment_proof_reviewed_by' => $actor->id,
                'payment_proof_reviewed_at' => now(),
            ]);
            $fresh = $request->fresh();
            $this->logActivity($actor, $fresh, null, 'payment_proof_revision_requested', $oldProofStatus, KpExamRequest::PAYMENT_PROOF_REVISION, $note, ['request_status' => $request->status]);

            return $fresh;
        });
    }

    public function replacePaymentProof(User $studentUser, KpExamRequest $request, array $paymentProof): KpExamRequest
    {
        $request->loadMissing('assignment');
        $this->ensureStudentOwnsAssignment($studentUser, $request->assignment);

        if (! $request->canReplacePaymentProof()) {
            throw ValidationException::withMessages(['payment_proof' => 'Bukti pembayaran pada pengajuan ini tidak bisa diubah.']);
        }
        if (! $this->paymentProofAvailable($paymentProof)) {
            throw ValidationException::withMessages(['payment_proof' => 'Upload bukti pembayaran KP atau tempel link Drive bukti pembayaran pengganti.']);
        }

        return DB::transaction(function () use ($studentUser, $request, $paymentProof) {
            $oldPath = $request->payment_proof_path;
            $oldDisk = $request->payment_proof_disk ?: 'local';
            $oldStatus = $request->paymentProofStatus();

            $request->update([
                'payment_proof_url' => null,
                'payment_proof_label' => null,
                'payment_proof_original_filename' => null,
                'payment_proof_path' => null,
                'payment_proof_disk' => null,
                'payment_proof_mime' => null,
                'payment_proof_size' => null,
                'payment_proof_status' => KpExamRequest::PAYMENT_PROOF_PENDING,
                'payment_proof_review_note' => null,
                'payment_proof_reviewed_by' => null,
                'payment_proof_reviewed_at' => null,
                ...$this->paymentProofPayload($paymentProof),
            ]);

            if ($oldPath) {
                Storage::disk($oldDisk)->delete($oldPath);
            }

            $fresh = $request->fresh();
            $this->logActivity($studentUser, $fresh, null, 'payment_proof_replaced', $oldStatus, KpExamRequest::PAYMENT_PROOF_PENDING, $paymentProof['label'] ?? null);

            return $fresh;
        });
    }

    public function requestRevision(User $actor, KpExamRequest $request, string $note): KpExamRequest
    {
        $old = $request->status;
        $request->update(['status' => 'revisi', 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_note' => $note]);
        $this->logActivity($actor, $request->fresh(), null, 'request_revision_requested', $old, 'revisi', $note);

        return $request->fresh();
    }

    public function rejectRequest(User $actor, KpExamRequest $request, string $note): KpExamRequest
    {
        $old = $request->status;
        $request->update(['status' => 'ditolak', 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_note' => $note]);
        $this->logActivity($actor, $request->fresh(), null, 'request_rejected', $old, 'ditolak', $note);

        return $request->fresh();
    }

    public function cancelRequest(User $actor, KpExamRequest $request, ?string $note = null): KpExamRequest
    {
        if (! in_array($request->status, ['draft', 'diajukan', 'revisi'], true)) {
            throw ValidationException::withMessages(['request' => 'Pengajuan sidang ini tidak bisa dibatalkan.']);
        }

        $old = $request->status;
        $request->update(['status' => 'dibatalkan', 'review_note' => $note]);
        $this->logActivity($actor, $request->fresh(), null, 'request_cancelled', $old, 'dibatalkan', $note);

        return $request->fresh();
    }

    public function scheduleExam(User $actor, KpExamRequest $request, array $data): KpExam
    {
        return DB::transaction(function () use ($actor, $request, $data) {
            $request = KpExamRequest::with('assignment')->lockForUpdate()->findOrFail($request->id);
            if (! $request->canBeScheduled()) {
                throw ValidationException::withMessages(['request' => 'Pengajuan ini tidak bisa dijadwalkan pada status saat ini.']);
            }
            if ($request->exam()->exists()) {
                throw ValidationException::withMessages(['request' => 'Sidang untuk pengajuan ini sudah dijadwalkan.']);
            }

            $assignment = $request->assignment;
            $this->ensureRequestEligibleForScheduling($request);

            if (! $assignment->internal_supervisor_id) {
                throw ValidationException::withMessages(['supervisor_id' => 'Pembimbing dalam belum ditentukan.']);
            }

            $data['chair_lecturer_id'] ??= $assignment->internal_supervisor_id;

            $examinerIds = $this->examinerIdsFrom($data);
            $this->ensureExaminers($examinerIds);

            $this->reserveMinutesNumber($data);

            $exam = KpExam::create($this->examPayload($request, $assignment, $actor, $data));
            $this->syncExaminers($exam, $examinerIds);
            $exam->update(['integration_revision' => 1]);
            $oldRequestStatus = $request->status;
            $request->update(['status' => 'dijadwalkan', 'reviewed_by' => $actor->id, 'reviewed_at' => now()]);
            $this->logActivity($actor, $request, $exam, 'exam_scheduled', $oldRequestStatus, 'dijadwalkan', $data['note'] ?? null, ['exam_date' => $data['exam_date'], 'examiner_ids' => $examinerIds, 'backdate_reason' => $exam->backdate_reason]);
            $this->outbox->enqueueExamScheduled($exam->fresh(['assignment.student.user', 'assignment.period', 'supervisor', 'examiner', 'examiners']));

            return $exam;
        });
    }

    public function rescheduleExam(User $actor, KpExam $exam, array $data): KpExam
    {
        return DB::transaction(function () use ($actor, $exam, $data) {
            $exam = KpExam::lockForUpdate()->findOrFail($exam->id);
            if (! $exam->canBeRescheduled()) {
                throw ValidationException::withMessages(['exam' => 'Sidang ini tidak bisa dijadwalkan ulang.']);
            }
            $oldExaminerIds = $exam->examinerIds();
            $data['chair_lecturer_id'] ??= $exam->supervisor_id;
            $examinerIds = $this->examinerIdsFrom($data);
            $this->ensureExaminers($examinerIds);
            $oldStatus = $exam->status;
            $exam->update([
                'examiner_id' => $examinerIds[0],
                'chair_lecturer_id' => $data['chair_lecturer_id'],
                'exam_date' => $data['exam_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'mode' => $data['mode'],
                'room' => $data['room'] ?? null,
                'meeting_link' => $data['meeting_link'] ?? null,
                'status' => 'dijadwalkan',
                'note' => $data['note'] ?? null,
                'backdate_reason' => $this->backdateReason($data),
                'integration_revision' => ((int) $exam->integration_revision) + 1,
            ]);
            $this->syncExaminers($exam, $examinerIds);
            $this->logActivity($actor, $exam->request, $exam->fresh(), 'exam_rescheduled', $oldStatus, 'dijadwalkan', $data['note'] ?? null, ['examiner_ids' => $examinerIds, 'backdate_reason' => $this->backdateReason($data)]);
            $this->outbox->enqueueExamRescheduled($exam->fresh(['assignment.student.user', 'assignment.period', 'supervisor', 'examiner', 'examiners']), $oldExaminerIds, $data['note'] ?? null);

            return $exam->fresh(['examiners']);
        });
    }

    public function correctExaminers(User $actor, KpExam $exam, array $examinerIds, string $reason): KpExam
    {
        $this->ensureExaminers($examinerIds);

        return DB::transaction(function () use ($actor, $exam, $examinerIds, $reason): KpExam {
            $exam = KpExam::query()->lockForUpdate()->findOrFail($exam->id);
            $exam->load(['request', 'assignment.finalScore', 'examiners.user', 'examiner.user', 'chair', 'minutes']);
            $hasExaminerScores = $exam->assignment->scores()->where('assessor_type', 'penguji')->exists();
            if (! $exam->minutes && ! $hasExaminerScores && $exam->status !== 'selesai') {
                throw ValidationException::withMessages(['examiner_ids' => 'Belum ada nilai atau berita acara. Gunakan Edit Jadwal untuk mengganti penguji.']);
            }
            if ($exam->status === 'dibatalkan') {
                throw ValidationException::withMessages(['examiner_ids' => 'Sidang yang dibatalkan tidak dapat dikoreksi.']);
            }
            $oldExaminerIds = collect($exam->examinerIds())->map(fn ($id): int => (int) $id)->sort()->values();
            $newExaminerIds = collect($examinerIds)->map(fn ($id): int => (int) $id)->unique()->sort()->values();

            if ($oldExaminerIds->all() === $newExaminerIds->all()) {
                throw ValidationException::withMessages(['examiner_ids' => 'Pilih tim penguji yang berbeda untuk melakukan koreksi.']);
            }

            $removedLecturerIds = $oldExaminerIds->diff($newExaminerIds)->values();
            $removedUserIds = Lecturer::whereIn('id', $removedLecturerIds)->pluck('user_id')->filter()->values();
            $removedScores = $exam->assignment->scores()
                ->where('assessor_type', 'penguji')
                ->whereIn('assessor_user_id', $removedUserIds)
                ->get();
            $scoreSnapshot = $removedScores->map(fn ($score): array => $score->only([
                'id', 'kp_exam_id', 'kp_assessment_component_id', 'assessor_user_id', 'score',
                'weighted_score', 'note', 'status', 'submitted_at', 'locked_at',
            ]))->values()->all();

            foreach ($removedScores as $score) {
                \App\Models\KpScoreLog::create([
                    'kp_assignment_id' => $exam->kp_assignment_id,
                    'kp_score_id' => $score->id,
                    'user_id' => $actor->id,
                    'action' => 'examiner_score_voided',
                    'old_status' => $score->status,
                    'new_status' => 'voided',
                    'note' => $reason,
                    'metadata' => ['score' => $score->only(['score', 'weighted_score', 'note']), 'assessor_user_id' => $score->assessor_user_id],
                ]);
                $score->delete();
            }

            $finalScore = $exam->assignment->finalScore;
            $finalScoreSnapshot = $finalScore?->only([
                'id', 'final_score', 'final_grade', 'status', 'calculated_at', 'finalized_by',
                'finalized_at', 'published_at', 'note',
            ]);
            if ($finalScore) {
                $oldFinalStatus = $finalScore->status;
                $finalScore->update([
                    'final_score' => null,
                    'final_grade' => null,
                    'status' => 'draft',
                    'calculated_at' => null,
                    'finalized_by' => null,
                    'finalized_at' => null,
                    'published_at' => null,
                    'note' => $reason,
                ]);
                \App\Models\KpScoreLog::create([
                    'kp_assignment_id' => $exam->kp_assignment_id,
                    'kp_final_score_id' => $finalScore->id,
                    'user_id' => $actor->id,
                    'action' => 'final_score_reopened_for_examiner_correction',
                    'old_status' => $oldFinalStatus,
                    'new_status' => 'draft',
                    'note' => $reason,
                    'metadata' => ['previous_final_score' => $finalScoreSnapshot],
                ]);
            }

            $minuteSnapshot = $exam->minutes?->attributesToArray();
            if ($exam->minutes) {
                \App\Models\KpDocumentSignature::query()
                    ->where('document_type', \App\Models\KpDocumentSignature::DOCUMENT_MINUTE)
                    ->where('document_id', $exam->minutes->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'revoked',
                        'revoked_at' => now(),
                        'revoked_by' => $actor->id,
                        'revocation_reason' => $reason,
                    ]);
            }
            $exam->minutes?->delete();
            $oldStatus = $exam->status;
            $chairId = $removedLecturerIds->contains((int) $exam->chair_lecturer_id)
                ? $exam->supervisor_id
                : $exam->chair_lecturer_id;
            $exam->update([
                'examiner_id' => $newExaminerIds->first(),
                'chair_lecturer_id' => $chairId,
                'status' => 'dijadwalkan',
                'note' => $reason,
                'integration_revision' => ((int) $exam->integration_revision) + 1,
            ]);
            $this->syncExaminers($exam, $newExaminerIds->all());
            $exam->request?->update(['status' => 'dijadwalkan']);

            $metadata = [
                'old_examiner_ids' => $oldExaminerIds->all(),
                'new_examiner_ids' => $newExaminerIds->all(),
                'voided_scores' => $scoreSnapshot,
                'previous_final_score' => $finalScoreSnapshot,
                'revoked_minute' => $minuteSnapshot,
            ];
            $this->logActivity($actor, $exam->request, $exam->fresh(), 'examiner_assignment_corrected', $oldStatus, 'dijadwalkan', $reason, $metadata);
            $this->outbox->enqueueExamRescheduled(
                $exam->fresh(['assignment.student.user', 'assignment.period', 'supervisor', 'examiner', 'examiners']),
                $oldExaminerIds->all(),
                $reason,
            );

            return $exam->fresh(['examiners', 'minutes']);
        });
    }

    public function cancelExam(User $actor, KpExam $exam, string $reason): void
    {
        DB::transaction(function () use ($actor, $exam, $reason): void {
            $exam = KpExam::query()->lockForUpdate()->findOrFail($exam->id);
            $old = $exam->status;
            $exam->update([
                'status' => 'dibatalkan',
                'note' => $reason,
                'integration_revision' => ((int) $exam->integration_revision) + 1,
            ]);
            $this->logActivity($actor, $exam->request, $exam->fresh(), 'exam_cancelled', $old, 'dibatalkan', $reason);
            $this->outbox->enqueueExamCancelled($exam->fresh(['assignment.student.user', 'assignment.period', 'supervisor', 'examiner', 'examiners']), $reason);
        });
    }

    public function completeExam(User $actor, KpExam $exam, ?string $note = null): void
    {
        DB::transaction(function () use ($actor, $exam, $note): void {
            $exam = KpExam::query()->lockForUpdate()->findOrFail($exam->id);
            $old = $exam->status;
            $exam->update([
                'status' => 'selesai',
                'note' => $note,
                'integration_revision' => ((int) $exam->integration_revision) + 1,
            ]);
            $this->logActivity($actor, $exam->request, $exam->fresh(), 'exam_completed', $old, 'selesai', $note);
            $this->outbox->enqueueExamCompleted($exam->fresh(['assignment.student.user', 'assignment.period', 'supervisor', 'examiner', 'examiners']));
        });
    }

    public function logActivity(User $user, KpExamRequest $request, ?KpExam $exam, string $action, ?string $oldStatus, ?string $newStatus, ?string $note = null, ?array $metadata = null): void
    {
        $request->logs()->create([
            'kp_exam_id' => $exam?->id,
            'user_id' => $user->id,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $note,
            'metadata' => $metadata,
        ]);
    }

    private function ensureStudentOwnsAssignment(User $studentUser, KpAssignment $assignment): void
    {
        if (! $studentUser->student || $studentUser->student->id !== $assignment->student_id) {
            abort(403, 'Anda tidak berhak mengajukan sidang untuk penempatan ini.');
        }
    }

    private function ensureRequestEligibleForScheduling(KpExamRequest $request): void
    {
        $request->loadMissing('assignment.finalReport');
        $assignment = $request->assignment;

        if (! $assignment || ! $assignment->isEligibleForExamRequest()) {
            $pending = $assignment ? collect($assignment->examEligibility()['items'])->first(fn (array $item): bool => $item['required_for_scheduling'] && ! $item['ready']) : null;

            throw ValidationException::withMessages([
                'request' => 'Validasi akhir belum bisa dilakukan. Lengkapi: '.($pending['label'] ?? 'syarat sidang').'.',
            ]);
        }

    }

    private function paymentProofAvailable(array $paymentProof): bool
    {
        return ($paymentProof['file'] ?? null) instanceof UploadedFile || filled($paymentProof['url'] ?? null);
    }

    private function paymentProofPayload(array $paymentProof): array
    {
        $payload = [
            'payment_proof_url' => $paymentProof['url'] ?? null,
            'payment_proof_label' => $paymentProof['label'] ?? null,
            'payment_proof_status' => KpExamRequest::PAYMENT_PROOF_PENDING,
            'payment_proof_review_note' => null,
            'payment_proof_reviewed_by' => null,
            'payment_proof_reviewed_at' => null,
        ];

        $file = $paymentProof['file'] ?? null;
        if ($file instanceof UploadedFile) {
            $path = $file->store('kp-exam-payment-proofs', 'local');
            $payload += [
                'payment_proof_original_filename' => $file->getClientOriginalName(),
                'payment_proof_path' => $path,
                'payment_proof_disk' => 'local',
                'payment_proof_mime' => $file->getClientMimeType(),
                'payment_proof_size' => $file->getSize(),
            ];
        }

        return $payload;
    }

    private function ensureExaminers(array $examinerIds): void
    {
        if (count($examinerIds) < 2 || count($examinerIds) > 3) {
            throw ValidationException::withMessages(['examiner_ids' => 'Pilih minimal 2 dan maksimal 3 penguji.']);
        }

        $examiners = Lecturer::with('user.roles')->whereIn('id', $examinerIds)->get();
        if ($examiners->count() !== count($examinerIds)) {
            throw ValidationException::withMessages(['examiner_ids' => 'Data penguji tidak valid.']);
        }

        $invalid = $examiners->first(fn (Lecturer $examiner): bool => ! $examiner->user?->hasRole('penguji'));
        if ($invalid) {
            throw ValidationException::withMessages(['examiner_ids' => 'Semua penguji harus memiliki role Penguji.']);
        }
    }

    private function examPayload(KpExamRequest $request, KpAssignment $assignment, User $actor, array $data): array
    {
        return [
            'kp_exam_request_id' => $request->id,
            'kp_assignment_id' => $assignment->id,
            'supervisor_id' => $assignment->internal_supervisor_id,
            'examiner_id' => $this->examinerIdsFrom($data)[0],
            'chair_lecturer_id' => $data['chair_lecturer_id'],
            'minutes_sequence' => $data['minutes_sequence'],
            'minutes_number' => $data['minutes_number'],
            'exam_date' => $data['exam_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'mode' => $data['mode'],
            'room' => $data['room'] ?? null,
            'meeting_link' => $data['meeting_link'] ?? null,
            'status' => 'dijadwalkan',
            'scheduled_by' => $actor->id,
            'scheduled_at' => now(),
            'note' => $data['note'] ?? null,
            'backdate_reason' => $this->backdateReason($data),
        ];
    }

    private function backdateReason(array $data): ?string
    {
        if (! Carbon::parse($data['exam_date'])->startOfDay()->lt(today())) {
            return null;
        }

        return trim((string) ($data['backdate_reason'] ?? '')) ?: null;
    }

    private function examinerIdsFrom(array $data): array
    {
        return collect($data['examiner_ids'] ?? [$data['examiner_id'] ?? null])
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function syncExaminers(KpExam $exam, array $examinerIds): void
    {
        $sync = collect($examinerIds)
            ->values()
            ->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort_order' => $index + 1]])
            ->all();

        $exam->examiners()->sync($sync);
    }

    private function reserveMinutesNumber(array &$data): void
    {
        $date = \Illuminate\Support\Carbon::parse($data['exam_date']);
        $sequence = ((int) KpExam::query()
            ->whereYear('exam_date', $date->year)
            ->lockForUpdate()
            ->max('minutes_sequence')) + 1;
        $roman = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$date->month];

        $data['minutes_sequence'] = $sequence;
        $data['minutes_number'] = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT).'/BA-SKP/FF-UBP/'.$roman.'/'.$date->year;
    }
}
