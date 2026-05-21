<?php

namespace App\Services;

use App\Models\KpAssignment;
use App\Models\KpFinalReport;
use App\Models\KpFinalReportFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KpFinalReportService
{
    public function createOrGetReport(User $studentUser, KpAssignment $assignment): KpFinalReport
    {
        $this->ensureStudentOwnsAssignment($studentUser, $assignment);
        $this->ensureAssignmentAcceptsReport($assignment);

        return DB::transaction(function () use ($studentUser, $assignment) {
            $report = KpFinalReport::firstOrCreate(
                ['kp_assignment_id' => $assignment->id],
                ['current_version' => 1, 'status' => 'draft']
            );

            if ($report->wasRecentlyCreated) {
                $this->logActivity($studentUser, $report, 'created', null, 'draft', 'Draft laporan akhir dibuat.');
            }

            return $report;
        });
    }

    public function uploadFile(User $studentUser, KpFinalReport $report, UploadedFile $file, ?string $note = null): KpFinalReportFile
    {
        $report->loadMissing('assignment');
        $this->ensureStudentOwnsAssignment($studentUser, $report->assignment);

        if (! $report->canBeEditedByStudent()) {
            throw ValidationException::withMessages(['report' => 'Laporan yang sudah disetujui atau menunggu review tidak bisa diubah.']);
        }

        return DB::transaction(function () use ($studentUser, $report, $file, $note) {
            $hadFile = $report->files()->exists();
            $version = $hadFile ? $report->files()->max('version') + 1 : 1;
            $path = $file->store('kp-final-reports', 'local');

            $reportFile = $report->files()->create([
                'version' => $version,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_disk' => 'local',
                'file_mime' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $studentUser->id,
                'uploaded_at' => now(),
                'note' => $note,
            ]);

            $oldStatus = $report->status;
            $report->update([
                'current_version' => $version,
                'status' => 'draft',
                'review_note' => $oldStatus === 'draft' ? $report->review_note : null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_at' => null,
            ]);

            $this->logActivity($studentUser, $report->fresh(), $hadFile ? 'revision_uploaded' : 'uploaded', $oldStatus, 'draft', $note, ['version' => $version]);

            return $reportFile;
        });
    }

    public function submit(User $studentUser, KpFinalReport $report): KpFinalReport
    {
        $report->loadMissing('assignment');
        $this->ensureStudentOwnsAssignment($studentUser, $report->assignment);

        if (! $report->files()->exists()) {
            throw ValidationException::withMessages(['file' => 'Upload file laporan terlebih dahulu sebelum submit.']);
        }
        if (! $report->canBeEditedByStudent()) {
            throw ValidationException::withMessages(['report' => 'Laporan tidak bisa disubmit pada status saat ini.']);
        }

        $oldStatus = $report->status;
        $report->update(['status' => 'menunggu_review', 'submitted_at' => now()]);
        $this->logActivity($studentUser, $report->fresh(), 'submitted', $oldStatus, 'menunggu_review', 'Laporan dikirim untuk review.');

        return $report->fresh();
    }

    public function approve(User $lecturerUser, KpFinalReport $report, ?string $note = null): KpFinalReport
    {
        $this->ensureLecturerCanReview($lecturerUser, $report);
        $this->ensureCanReview($report);

        return $this->review($lecturerUser, $report, 'disetujui', 'approved', $note, ['approved_at' => now()]);
    }

    public function requestRevision(User $lecturerUser, KpFinalReport $report, string $note): KpFinalReport
    {
        $this->ensureLecturerCanReview($lecturerUser, $report);
        $this->ensureCanReview($report);

        return $this->review($lecturerUser, $report, 'revisi', 'revision_requested', $note);
    }

    public function reject(User $lecturerUser, KpFinalReport $report, string $note): KpFinalReport
    {
        $this->ensureLecturerCanReview($lecturerUser, $report);
        $this->ensureCanReview($report);

        return $this->review($lecturerUser, $report, 'ditolak', 'rejected', $note);
    }

    public function logActivity(User $user, KpFinalReport $report, string $action, ?string $oldStatus, ?string $newStatus, ?string $note = null, ?array $metadata = null): void
    {
        $report->logs()->create([
            'user_id' => $user->id,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $note,
            'metadata' => $metadata,
        ]);
    }

    public function ensureStudentOwnsReport(User $studentUser, KpFinalReport $report): void
    {
        $report->loadMissing('assignment');
        $this->ensureStudentOwnsAssignment($studentUser, $report->assignment);
    }

    public function ensureLecturerCanReview(User $lecturerUser, KpFinalReport $report): void
    {
        $report->loadMissing('assignment');
        if (! $lecturerUser->lecturer || $lecturerUser->lecturer->id !== $report->assignment->internal_supervisor_id) {
            abort(403, 'Anda tidak berhak mereview laporan ini.');
        }
    }

    public function ensureStudentCanDownload(User $studentUser, KpFinalReportFile $file): void
    {
        $file->loadMissing('report.assignment');
        $this->ensureStudentOwnsAssignment($studentUser, $file->report->assignment);
    }

    public function ensureLecturerCanDownload(User $lecturerUser, KpFinalReportFile $file): void
    {
        $file->loadMissing('report.assignment');
        if (! $lecturerUser->lecturer || $lecturerUser->lecturer->id !== $file->report->assignment->internal_supervisor_id) {
            abort(403);
        }
    }

    private function review(User $reviewer, KpFinalReport $report, string $newStatus, string $action, ?string $note, array $extra = []): KpFinalReport
    {
        $oldStatus = $report->status;
        $report->update([
            'status' => $newStatus,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ] + $extra);
        $this->logActivity($reviewer, $report->fresh(), $action, $oldStatus, $newStatus, $note);

        return $report->fresh();
    }

    private function ensureStudentOwnsAssignment(User $studentUser, KpAssignment $assignment): void
    {
        if (! $studentUser->student || $studentUser->student->id !== $assignment->student_id) {
            abort(403, 'Anda tidak berhak mengelola laporan ini.');
        }
    }

    private function ensureAssignmentAcceptsReport(KpAssignment $assignment): void
    {
        if (! in_array($assignment->status, ['aktif', 'berjalan'], true)) {
            throw ValidationException::withMessages(['assignment' => 'Laporan akhir hanya bisa dibuat jika penempatan KP aktif atau berjalan.']);
        }
    }

    private function ensureCanReview(KpFinalReport $report): void
    {
        if ($report->status !== 'menunggu_review') {
            throw ValidationException::withMessages(['report' => 'Laporan belum disubmit atau sudah selesai direview.']);
        }
    }
}
