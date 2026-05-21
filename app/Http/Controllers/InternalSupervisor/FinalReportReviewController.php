<?php

namespace App\Http\Controllers\InternalSupervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\ReviewFinalReportRequest;
use App\Models\KpFinalReport;
use App\Models\KpFinalReportFile;
use App\Services\KpFinalReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinalReportReviewController extends Controller
{
    public function index(Request $request): View
    {
        $lecturerId = $request->user()->lecturer?->id;
        $reports = KpFinalReport::query()
            ->with(['assignment.student.user', 'assignment.period', 'assignment.place', 'latestFile'])
            ->whereHas('assignment', fn ($q) => $q->where('internal_supervisor_id', $lecturerId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = $request->q;
                $q->whereHas('assignment.student', fn ($student) => $student->where('nim', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%")));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('internal-supervisor.final-reports.index', ['reports' => $reports, 'filters' => $request->only(['status', 'q'])]);
    }

    public function show(KpFinalReport $report, KpFinalReportService $service): View
    {
        $service->ensureLecturerCanReview(request()->user(), $report);

        return view('internal-supervisor.final-reports.show', [
            'report' => $report->load(['assignment.student.user', 'assignment.period', 'assignment.place', 'files.uploadedBy', 'logs.user', 'latestFile']),
        ]);
    }

    public function approve(ReviewFinalReportRequest $request, KpFinalReport $report, KpFinalReportService $service): RedirectResponse
    {
        $service->approve($request->user(), $report, $request->review_note);

        return back()->with('status', 'Laporan akhir berhasil disetujui.');
    }

    public function revision(ReviewFinalReportRequest $request, KpFinalReport $report, KpFinalReportService $service): RedirectResponse
    {
        if (! $request->filled('review_note')) {
            throw ValidationException::withMessages(['review_note' => 'Catatan revisi wajib diisi.']);
        }

        $service->requestRevision($request->user(), $report, $request->review_note);

        return back()->with('status', 'Revisi laporan berhasil diminta.');
    }

    public function reject(ReviewFinalReportRequest $request, KpFinalReport $report, KpFinalReportService $service): RedirectResponse
    {
        if (! $request->filled('review_note')) {
            throw ValidationException::withMessages(['review_note' => 'Catatan penolakan wajib diisi.']);
        }

        $service->reject($request->user(), $report, $request->review_note);

        return back()->with('status', 'Laporan akhir berhasil ditolak.');
    }

    public function download(KpFinalReportFile $file, KpFinalReportService $service): StreamedResponse
    {
        $service->ensureLecturerCanDownload(request()->user(), $file);

        return Storage::disk($file->file_disk ?: 'local')->download($file->file_path, $file->original_filename);
    }
}
