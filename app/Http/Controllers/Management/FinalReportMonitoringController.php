<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpFinalReport;
use App\Models\KpFinalReportFile;
use App\Models\KpPeriod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinalReportMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $internalStatus = $request->string('internal_status')->toString();
        $fieldStatus = $request->string('field_status')->toString();
        $sort = $request->string('sort', 'follow_up')->toString();

        $reports = KpFinalReport::query()
            ->with(['assignment.student.user', 'assignment.period', 'assignment.place', 'assignment.internalSupervisor.user', 'assignment.fieldSupervisor.user', 'latestFile'])
            ->when($request->filled('period'), fn ($q) => $q->whereHas('assignment', fn ($assignment) => $assignment->where('kp_period_id', $request->period)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($internalStatus === 'guidance_pending', fn ($q) => $q->whereNull('internal_guidance_completed_at'))
            ->when($internalStatus === 'guidance_completed', fn ($q) => $q->whereNotNull('internal_guidance_completed_at'))
            ->when($internalStatus === 'report_pending', fn ($q) => $q->where(fn ($status) => $status->whereNull('internal_review_status')->orWhere('internal_review_status', '!=', 'disetujui')))
            ->when($internalStatus === 'report_approved', fn ($q) => $q->where('internal_review_status', 'disetujui'))
            ->when($internalStatus === 'completed', fn ($q) => $q->whereNotNull('internal_guidance_completed_at')->where('internal_review_status', 'disetujui'))
            ->when($fieldStatus === 'guidance_pending', fn ($q) => $q->whereNull('field_guidance_completed_at'))
            ->when($fieldStatus === 'guidance_completed', fn ($q) => $q->whereNotNull('field_guidance_completed_at'))
            ->when($fieldStatus === 'report_pending', fn ($q) => $q->where(fn ($status) => $status->whereNull('field_review_status')->orWhere('field_review_status', '!=', 'disetujui')))
            ->when($fieldStatus === 'report_approved', fn ($q) => $q->where('field_review_status', 'disetujui'))
            ->when($fieldStatus === 'completed', fn ($q) => $q->whereNotNull('field_guidance_completed_at')->where('field_review_status', 'disetujui'))
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = $request->q;
                $q->whereHas('assignment.student', fn ($student) => $student->where('nim', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%")))
                    ->orWhereHas('assignment.place', fn ($place) => $place->where('name', 'like', "%{$keyword}%"));
            })
            ->when($sort === 'follow_up', fn ($q) => $q
                ->orderByRaw("CASE WHEN internal_guidance_completed_at IS NULL OR field_guidance_completed_at IS NULL OR internal_review_status IS NULL OR internal_review_status != 'disetujui' OR field_review_status IS NULL OR field_review_status != 'disetujui' THEN 0 ELSE 1 END")
                ->latest())
            ->when($sort === 'latest', fn ($q) => $q->latest())
            ->when($sort === 'student', fn ($q) => $q->orderBy(
                User::query()->select('users.name')
                    ->join('students', 'students.user_id', '=', 'users.id')
                    ->join('kp_assignments', 'kp_assignments.student_id', '=', 'students.id')
                    ->whereColumn('kp_assignments.id', 'kp_final_reports.kp_assignment_id')
                    ->limit(1)
            ))
            ->when(! in_array($sort, ['follow_up', 'latest', 'student'], true), fn ($q) => $q->latest())
            ->paginate(12)
            ->withQueryString();

        $needsFollowUp = function ($status) {
            $status->whereNull('internal_guidance_completed_at')
                ->orWhereNull('field_guidance_completed_at')
                ->orWhereNull('internal_review_status')
                ->orWhere('internal_review_status', '!=', 'disetujui')
                ->orWhereNull('field_review_status')
                ->orWhere('field_review_status', '!=', 'disetujui');
        };

        return view('management.final-reports.index', [
            'reports' => $reports,
            'periods' => KpPeriod::latest()->get(),
            'filters' => array_merge(
                $request->only(['period', 'status', 'internal_status', 'field_status', 'q']),
                ['sort' => in_array($sort, ['follow_up', 'latest', 'student'], true) ? $sort : 'latest']
            ),
            'stats' => [
                'total' => KpFinalReport::count(),
                'follow_up' => KpFinalReport::query()->where($needsFollowUp)->count(),
                'internal_complete' => KpFinalReport::whereNotNull('internal_guidance_completed_at')->where('internal_review_status', 'disetujui')->count(),
                'field_complete' => KpFinalReport::whereNotNull('field_guidance_completed_at')->where('field_review_status', 'disetujui')->count(),
                'complete' => KpFinalReport::whereNotNull('internal_guidance_completed_at')
                    ->whereNotNull('field_guidance_completed_at')
                    ->where('internal_review_status', 'disetujui')
                    ->where('field_review_status', 'disetujui')
                    ->count(),
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
