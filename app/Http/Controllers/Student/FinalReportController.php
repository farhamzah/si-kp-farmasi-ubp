<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitFinalReportRequest;
use App\Http\Requests\Student\UploadFinalReportRequest;
use App\Models\KpAssignment;
use App\Models\KpFinalReportFile;
use App\Services\KpFinalReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinalReportController extends Controller
{
    public function show(KpFinalReportService $service): View
    {
        $assignment = $this->activeAssignment();
        $report = $assignment ? $service->createOrGetReport(request()->user(), $assignment)->load(['latestFile', 'files.uploadedBy', 'logs.user']) : null;

        return view('student.final-reports.show', [
            'assignment' => $assignment?->load(['place', 'internalSupervisor.user']),
            'report' => $report,
        ]);
    }

    public function upload(UploadFinalReportRequest $request, KpFinalReportService $service): RedirectResponse
    {
        $assignment = $this->requireActiveAssignment();
        $report = $service->createOrGetReport($request->user(), $assignment);
        $service->uploadFile($request->user(), $report, $request->file('report_file'), $request->note);

        return back()->with('status', 'File laporan akhir berhasil diupload.');
    }

    public function submit(SubmitFinalReportRequest $request, KpFinalReportService $service): RedirectResponse
    {
        $assignment = $this->requireActiveAssignment();
        $report = $service->createOrGetReport($request->user(), $assignment);
        $service->submit($request->user(), $report);

        return back()->with('status', 'Laporan akhir dikirim untuk review pembimbing dalam.');
    }

    public function download(KpFinalReportFile $file, KpFinalReportService $service): StreamedResponse
    {
        $service->ensureStudentCanDownload(request()->user(), $file);

        return Storage::disk($file->file_disk ?: 'local')->download($file->file_path, $file->original_filename);
    }

    private function activeAssignment(): ?KpAssignment
    {
        return request()->user()->student?->assignments()
            ->with(['place', 'internalSupervisor.user'])
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
}
