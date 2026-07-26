<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitFinalReportRequest;
use App\Http\Requests\Student\UploadFinalReportRequest;
use App\Models\KpAssignment;
use App\Models\KpFinalReportFile;
use App\Services\KpFinalReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinalReportController extends Controller
{
    public function show(KpFinalReportService $service): View
    {
        $assignment = $this->activeAssignment();
        $report = $assignment ? $service->createOrGetReport(request()->user(), $assignment)->load(['latestFile', 'files.uploadedBy', 'logs.user', 'internalReviewedBy', 'fieldReviewedBy']) : null;

        return view('student.final-reports.show', [
            'assignment' => $assignment?->load(['place', 'internalSupervisor.user', 'fieldSupervisor.user', 'reportGuidanceLogs.validatedBy']),
            'report' => $report,
            'examEligibility' => $assignment?->examEligibility(),
        ]);
    }

    public function upload(UploadFinalReportRequest $request, KpFinalReportService $service): RedirectResponse
    {
        $assignment = $this->requireActiveAssignment();
        $report = $service->createOrGetReport($request->user(), $assignment);
        $service->uploadFile($request->user(), $report, $request->file('report_file'), $request->note);

        return back()->with('status', 'File laporan akhir berhasil diupload.');
    }

    public function saveFinalLink(Request $request, KpFinalReportService $service): RedirectResponse
    {
        $request->merge([
            'final_document_url' => $this->normalizeDocumentUrl($request->input('final_document_url')),
            'final_document_label' => $this->cleanNullableText($request->input('final_document_label')),
        ]);

        $data = $request->validate([
            'final_document_url' => ['required', 'url:http,https', 'max:2048'],
            'final_document_label' => ['nullable', 'string', 'max:255'],
        ]);

        $assignment = $this->requireActiveAssignment();
        $report = $service->createOrGetReport($request->user(), $assignment);
        $service->saveFinalDocumentLink($request->user(), $report, $data['final_document_url'], $data['final_document_label'] ?? null);

        return back()->with('status', 'Link laporan final berhasil disimpan.');
    }

    public function storeGuidance(Request $request, KpFinalReportService $service): RedirectResponse
    {
        $request->merge([
            'document_url' => $this->normalizeDocumentUrl($request->input('document_url')),
            'document_label' => $this->cleanNullableText($request->input('document_label')),
        ]);

        $data = $request->validate([
            'reviewer_type' => ['required', 'in:internal,field'],
            'guidance_date' => ['required', 'date'],
            'topic' => ['required', 'string', 'max:255'],
            'student_note' => ['nullable', 'string', 'max:2000'],
            'document_url' => ['nullable', 'url:http,https', 'max:2048'],
            'document_label' => ['nullable', 'string', 'max:255'],
        ]);

        $service->addGuidanceLog($request->user(), $this->requireActiveAssignment(), $data);

        return back()->with('status', 'Log bimbingan laporan berhasil dikirim untuk validasi pembimbing terkait.');
    }

    public function submit(SubmitFinalReportRequest $request, KpFinalReportService $service): RedirectResponse
    {
        $assignment = $this->requireActiveAssignment();
        $report = $service->createOrGetReport($request->user(), $assignment);
        $service->submit($request->user(), $report);

        return back()->with('status', 'Laporan akhir dikirim untuk review pembimbing dalam dan pembimbing lapangan.');
    }

    public function download(KpFinalReportFile $file, KpFinalReportService $service): StreamedResponse
    {
        $service->ensureStudentCanDownload(request()->user(), $file);

        return Storage::disk($file->file_disk ?: 'local')->download($file->file_path, $file->original_filename);
    }

    private function activeAssignment(): ?KpAssignment
    {
        return request()->user()->student?->assignments()
            ->with(['place', 'internalSupervisor.user', 'fieldSupervisor.user'])
            ->whereIn('status', ['aktif', 'berjalan'])
            ->latest()
            ->first();
    }

    private function requireActiveAssignment(): KpAssignment
    {
        $assignment = $this->activeAssignment();

        if (! $assignment) {
            throw ValidationException::withMessages(['assignment' => 'Anda belum memiliki penempatan KP aktif.']);
        }

        return $assignment;
    }

    private function normalizeDocumentUrl(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $url = trim($value);
        $url = trim($url, "\"'<>");
        $url = preg_replace('/\s+/', '', $url) ?? $url;

        if ($url === '') {
            return null;
        }

        if (! preg_match('~^[a-z][a-z0-9+.-]*://~i', $url) && preg_match('~^(drive\.google\.com/|docs\.google\.com/|forms\.gle/)~i', $url)) {
            $url = 'https://'.$url;
        }

        return $url;
    }

    private function cleanNullableText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
