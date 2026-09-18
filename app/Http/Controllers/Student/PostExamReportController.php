<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\KpAssignment;
use App\Models\KpPostExamReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostExamReportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $assignment = $this->eligibleAssignment($request);
        $report = KpPostExamReport::firstOrNew(['kp_assignment_id' => $assignment->id]);

        if ($report->exists && ! $report->canBeEditedByStudent()) {
            throw ValidationException::withMessages(['document' => 'Dokumen sedang divalidasi atau sudah disetujui koordinator.']);
        }

        $data = $request->validate([
            'document_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480', 'required_without:document_url'],
            'document_url' => ['nullable', 'url:http,https', 'max:2048', 'required_without:document_file'],
            'document_label' => ['nullable', 'string', 'max:255'],
        ]);

        $oldPath = $report->file_path;
        $oldDisk = $report->file_disk ?: 'local';
        $file = $request->file('document_file');
        $nextVersion = $report->exists && $report->hasDocument() ? $report->version + 1 : max(1, (int) $report->version);

        $payload = [
            'version' => $nextVersion,
            'status' => KpPostExamReport::STATUS_WAITING,
            'document_url' => $file ? null : ($data['document_url'] ?? null),
            'document_label' => $data['document_label'] ?? ($file?->getClientOriginalName()),
            'submitted_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
            'approved_at' => null,
        ];

        if ($file) {
            $payload += [
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $file->store('post-exam-reports/'.$assignment->id, 'local'),
                'file_disk' => 'local',
                'file_mime' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ];
        } else {
            $payload += ['original_filename' => null, 'file_path' => null, 'file_mime' => null, 'file_size' => null];
        }

        $report->fill($payload)->save();

        if ($oldPath && $oldPath !== $report->file_path) {
            Storage::disk($oldDisk)->delete($oldPath);
        }

        return back()->with('status', 'Dokumen final pascasidang berhasil dikirim untuk validasi koordinator.');
    }

    public function preview(Request $request): StreamedResponse
    {
        return $this->fileResponse($request, false);
    }

    public function download(Request $request): StreamedResponse
    {
        return $this->fileResponse($request, true);
    }

    private function fileResponse(Request $request, bool $download): StreamedResponse
    {
        $report = $this->studentAssignment($request)?->postExamReport;
        abort_unless($report?->file_path, 404);
        $disk = Storage::disk($report->file_disk ?: 'local');

        return $download
            ? $disk->download($report->file_path, $report->documentLabel())
            : $disk->response($report->file_path, $report->documentLabel(), array_filter(['Content-Type' => $report->file_mime]));
    }

    private function eligibleAssignment(Request $request): KpAssignment
    {
        $assignment = $this->studentAssignment($request);
        $assignment?->loadMissing('exam.minutes');

        if (! $assignment?->exam?->minutes || $assignment->exam->minutes->result === 'belum_lulus') {
            throw ValidationException::withMessages(['document' => 'Upload dokumen final pascasidang dibuka setelah sidang dinyatakan lulus atau lulus dengan revisi.']);
        }

        return $assignment;
    }

    private function studentAssignment(Request $request): ?KpAssignment
    {
        return $request->user()->student?->assignments()
            ->with(['postExamReport', 'exam.minutes'])
            ->whereIn('status', ['aktif', 'berjalan', 'selesai'])
            ->latest('assigned_at')
            ->first();
    }
}
