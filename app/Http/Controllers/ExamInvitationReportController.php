<?php

namespace App\Http\Controllers;

use App\Models\KpExam;
use App\Models\KpFinalReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamInvitationReportController extends Controller
{
    public function preview(Request $request, KpExam $exam): View
    {
        $report = $this->reportForParticipant($request, $exam);
        $file = $report->latestFile;
        $fileAvailable = $file && Storage::disk($file->file_disk ?: 'local')->exists($file->file_path);
        $documentUrl = $this->safeDocumentUrl($report->final_document_url);

        return view('exam-invitations.report', [
            'exam' => $exam,
            'report' => $report,
            'file' => $fileAvailable ? $file : null,
            'isPdf' => ! $documentUrl && $fileAvailable && $file->file_mime === 'application/pdf',
            'documentUrl' => $documentUrl,
        ]);
    }

    public function file(Request $request, KpExam $exam): StreamedResponse
    {
        $file = $this->reportForParticipant($request, $exam)->latestFile;
        abort_unless($file, 404);

        $disk = Storage::disk($file->file_disk ?: 'local');
        abort_unless($disk->exists($file->file_path), 404);

        if ($request->boolean('download') || $file->file_mime !== 'application/pdf') {
            return $disk->download($file->file_path, $file->original_filename);
        }

        return $disk->response($file->file_path, $file->original_filename, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function reportForParticipant(Request $request, KpExam $exam): KpFinalReport
    {
        $exam->loadMissing(['assignment.student.user', 'assignment.place', 'assignment.finalReport.latestFile']);
        $role = (string) $request->session()->get('active_role');
        $user = $request->user();

        $allowed = match ($role) {
            'admin', 'koordinator_kp' => true,
            'mahasiswa' => (int) $exam->assignment?->student?->user_id === (int) $user->id,
            'pembimbing_dalam' => (int) $exam->supervisor_id === (int) ($user->lecturer?->id ?: 0),
            'pembimbing_lapangan' => (int) $exam->assignment?->field_supervisor_id === (int) ($user->fieldSupervisor?->id ?: 0),
            'penguji' => $exam->hasExaminer($user->lecturer?->id),
            default => false,
        };

        abort_unless($allowed, 403);

        return $exam->assignment?->finalReport ?? abort(404);
    }

    private function safeDocumentUrl(?string $url): ?string
    {
        if (! $url || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return null;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), ['drive.google.com', 'docs.google.com'], true)
            ? preg_replace('/^http:/i', 'https:', $url)
            : null;
    }
}
