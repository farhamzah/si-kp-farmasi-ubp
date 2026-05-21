<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpFinalReport;
use App\Models\KpFinalReportFile;
use App\Models\KpPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinalReportMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $reports = KpFinalReport::query()
            ->with(['assignment.student.user', 'assignment.period', 'assignment.place', 'assignment.internalSupervisor.user', 'latestFile'])
            ->when($request->filled('period'), fn ($q) => $q->whereHas('assignment', fn ($assignment) => $assignment->where('kp_period_id', $request->period)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = $request->q;
                $q->whereHas('assignment.student', fn ($student) => $student->where('nim', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%")))
                    ->orWhereHas('assignment.place', fn ($place) => $place->where('name', 'like', "%{$keyword}%"));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('management.final-reports.index', [
            'reports' => $reports,
            'periods' => KpPeriod::latest()->get(),
            'filters' => $request->only(['period', 'status', 'q']),
            'stats' => [
                'total' => KpFinalReport::count(),
                'pending' => KpFinalReport::where('status', 'menunggu_review')->count(),
                'revision' => KpFinalReport::where('status', 'revisi')->count(),
                'approved' => KpFinalReport::where('status', 'disetujui')->count(),
                'rejected' => KpFinalReport::where('status', 'ditolak')->count(),
            ],
        ]);
    }

    public function show(KpFinalReport $report): View
    {
        return view('management.final-reports.show', [
            'report' => $report->load(['assignment.student.user', 'assignment.period', 'assignment.place', 'assignment.internalSupervisor.user', 'files.uploadedBy', 'logs.user', 'latestFile']),
        ]);
    }

    public function download(KpFinalReportFile $file): StreamedResponse
    {
        return Storage::disk($file->file_disk ?: 'local')->download($file->file_path, $file->original_filename);
    }
}
