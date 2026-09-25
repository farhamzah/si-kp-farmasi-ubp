<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\KpAssignment;
use App\Models\KpPostExamReport;
use App\Support\KpReportFilename;
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

        $request->merge(['document_url' => $this->normalizeDocumentUrl($request->input('document_url'))]);
        $data = $request->validate([
            'document_url' => ['required', 'url:http,https', 'max:2048'],
        ]);
        $this->ensureDocumentUrlIsGoogleFile($data['document_url']);

        $oldPath = $report->file_path;
        $oldDisk = $report->file_disk ?: 'local';
        $nextVersion = $report->exists && $report->hasDocument() ? $report->version + 1 : max(1, (int) $report->version);

        $payload = [
            'version' => $nextVersion,
            'status' => KpPostExamReport::STATUS_WAITING,
            'document_url' => $data['document_url'],
            'document_label' => KpReportFilename::postExam($assignment),
            'original_filename' => null,
            'file_path' => null,
            'file_disk' => 'local',
            'file_mime' => null,
            'file_size' => null,
            'submitted_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
            'approved_at' => null,
        ];

        $report->fill($payload)->save();

        if ($oldPath && $oldPath !== $report->file_path) {
            Storage::disk($oldDisk)->delete($oldPath);
        }

        return back()->with('status', 'Dokumen final pascasidang berhasil dikirim untuk validasi koordinator.');
    }

    private function ensureDocumentUrlIsGoogleFile(string $url): void
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $isFolder = str_contains($path, '/drive/folders/') || str_contains($path, '/folders/');
        $isDriveFile = $host === 'drive.google.com' && (
            str_starts_with($path, '/file/d/')
            || (in_array($path, ['/open', '/uc'], true) && filled($query['id'] ?? null))
        );
        $isDocsFile = $host === 'docs.google.com'
            && preg_match('~^/(document|spreadsheets|presentation)/d/[^/]+~', $path) === 1;

        if ((! $isDriveFile && ! $isDocsFile) || $isFolder) {
            throw ValidationException::withMessages([
                'document_url' => $isFolder
                    ? 'Tempel link file PDF yang sudah diupload, bukan link folder Google Drive.'
                    : 'Link dokumen harus berupa link file dari Google Drive.',
            ]);
        }
    }

    private function normalizeDocumentUrl(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $url = preg_replace('/\s+/', '', trim($value, " \t\n\r\0\x0B\"'<>")) ?? trim($value);
        if (! preg_match('~^[a-z][a-z0-9+.-]*://~i', $url) && preg_match('~^(drive\.google\.com/|docs\.google\.com/)~i', $url)) {
            $url = 'https://'.$url;
        }

        return $url;
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
