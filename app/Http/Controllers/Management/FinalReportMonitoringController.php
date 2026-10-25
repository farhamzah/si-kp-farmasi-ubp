<?php

namespace App\Http\Controllers\Management;

use App\Exports\KpTableReportExport;
use App\Http\Controllers\Controller;
use App\Models\KpFinalReport;
use App\Models\KpFinalReportFile;
use App\Models\KpPeriod;
use App\Models\KpReportGuidanceLog;
use App\Models\User;
use App\Services\KpFinalReportService;
use App\Support\SimplePdfReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinalReportMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->string('sort', 'follow_up')->toString();

        $reports = $this->query($request)
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

    public function preview(Request $request): View
    {
        return view('management.final-reports.report-preview', [
            'rows' => $this->reportRows($request),
            'filters' => $this->filterSummary($request),
            'printMode' => $request->boolean('print'),
            'reportQuery' => $request->only(['q', 'period', 'status', 'internal_status', 'field_status', 'sort']),
        ]);
    }

    public function reportDownload(string $format, Request $request): Response|BinaryFileResponse
    {
        abort_unless(in_array($format, ['pdf', 'excel', 'word'], true), 404);

        $rows = $this->reportRows($request);
        $filters = $this->filterSummary($request);
        $filename = 'monitoring-laporan-kp-'.now()->format('Ymd-His');

        if ($format === 'excel') {
            return Excel::download(new KpTableReportExport('Monitoring Laporan KP', $filters, $rows, $this->emptyReportRow()), $filename.'.xlsx');
        }

        if ($format === 'pdf') {
            return response(SimplePdfReport::table(
                'Monitoring Laporan KP',
                $filters + ['Total data' => $rows->count()],
                array_keys($rows->first() ?? $this->emptyReportRow()),
                $rows->map(fn (array $row) => array_values($row))->all()
            ), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
            ]);
        }

        return response()->view('management.final-reports.report-word', [
            'rows' => $rows,
            'filters' => $filters,
        ], 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.doc"',
        ]);
    }

    private function query(Request $request): Builder
    {
        $internalStatus = $request->string('internal_status')->toString();
        $fieldStatus = $request->string('field_status')->toString();
        $sort = $request->string('sort', 'follow_up')->toString();

        return KpFinalReport::query()
            ->with(['assignment.student.user', 'assignment.period', 'assignment.place', 'assignment.internalSupervisor.user', 'assignment.fieldSupervisor.user', 'latestFile'])
            ->when($request->filled('period'), fn ($q) => $q->whereHas('assignment', fn ($assignment) => $assignment->where('kp_period_id', $request->period)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($internalStatus === 'guidance_pending', fn ($q) => $q->whereNull('internal_guidance_completed_at'))
            ->when($internalStatus === 'guidance_completed', fn ($q) => $q->whereNotNull('internal_guidance_completed_at'))
            ->when($internalStatus === 'report_pending', fn ($q) => $q->where('status', 'menunggu_review')->where(fn ($status) => $status->whereNull('internal_review_status')->orWhere('internal_review_status', 'pending')))
            ->when($internalStatus === 'report_approved', fn ($q) => $q->where('internal_review_status', 'disetujui'))
            ->when($internalStatus === 'completed', fn ($q) => $q->whereNotNull('internal_guidance_completed_at')->where('internal_review_status', 'disetujui'))
            ->when($fieldStatus === 'guidance_pending', fn ($q) => $q->whereNull('field_guidance_completed_at'))
            ->when($fieldStatus === 'guidance_completed', fn ($q) => $q->whereNotNull('field_guidance_completed_at'))
            ->when($fieldStatus === 'report_pending', fn ($q) => $q->where('status', 'menunggu_review')->where(fn ($status) => $status->whereNull('field_review_status')->orWhere('field_review_status', 'pending')))
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
            ->when(! in_array($sort, ['follow_up', 'latest', 'student'], true), fn ($q) => $q->latest());
    }

    private function reportRows(Request $request): Collection
    {
        return $this->query($request)->get()->values()->map(function (KpFinalReport $report, int $index): array {
            $assignment = $report->assignment;

            return [
                'No' => $index + 1,
                'Mahasiswa' => $assignment->student->user->name,
                'NIM' => $assignment->student->nim ?: '-',
                'Periode' => $assignment->period?->name ?? '-',
                'Tempat KP' => $assignment->place?->name ?? '-',
                'Pembimbing Dalam' => ($assignment->internalSupervisor ? lecturer_display_name($assignment->internalSupervisor) : '-').'; Bimbingan: '.($report->isInternalGuidanceCompleted() ? 'Selesai' : 'Belum').'; Laporan: '.$report->internalReviewStatusLabel(),
                'Pembimbing Lapangan' => ($assignment->fieldSupervisor?->user?->name ?? '-').'; Bimbingan: '.($report->isFieldGuidanceCompleted() ? 'Selesai' : 'Belum').'; Laporan: '.$report->fieldReviewStatusLabel(),
                'Status Laporan' => $report->statusLabel(),
                'Versi' => $report->current_version,
            ];
        });
    }

    private function emptyReportRow(): array
    {
        return array_fill_keys(['No', 'Mahasiswa', 'NIM', 'Periode', 'Tempat KP', 'Pembimbing Dalam', 'Pembimbing Lapangan', 'Status Laporan', 'Versi'], '');
    }

    private function filterSummary(Request $request): array
    {
        $status = ['draft' => 'Draft', 'menunggu_review' => 'Menunggu Review', 'revisi' => 'Revisi', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
        $supervisorStatus = ['guidance_pending' => 'Bimbingan Belum Selesai', 'guidance_completed' => 'Bimbingan Selesai', 'report_pending' => 'Laporan Belum Disetujui', 'report_approved' => 'Laporan Disetujui', 'completed' => 'Lengkap'];
        $sort = ['follow_up' => 'Perlu tindak lanjut', 'latest' => 'Laporan terbaru', 'student' => 'Nama mahasiswa'];

        return [
            'Pencarian' => $request->filled('q') ? $request->string('q')->toString() : 'Semua',
            'Periode' => $request->filled('period') ? (KpPeriod::find($request->input('period'))?->name ?? 'Periode tidak ditemukan') : 'Semua',
            'Status laporan' => $status[$request->input('status')] ?? 'Semua',
            'Pembimbing dalam' => $supervisorStatus[$request->input('internal_status')] ?? 'Semua',
            'Pembimbing lapangan' => $supervisorStatus[$request->input('field_status')] ?? 'Semua',
            'Urutan' => $sort[$request->input('sort', 'follow_up')] ?? $sort['latest'],
            'Dicetak pada' => now()->format('d/m/Y H:i'),
        ];
    }

    public function show(KpFinalReport $report): View
    {
        return view('management.final-reports.show', [
            'report' => $report->load(['assignment.student.user', 'assignment.period', 'assignment.place', 'assignment.internalSupervisor.user', 'assignment.fieldSupervisor.user', 'assignment.reportGuidanceLogs.validatedBy', 'files.uploadedBy', 'logs.user', 'latestFile']),
        ]);
    }

    public function approveOnBehalf(Request $request, KpFinalReport $report, string $role, KpFinalReportService $service)
    {
        $this->ensureCoordinator($request);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $service->approveOnBehalf($request->user(), $report, $role, $data['reason']);

        return back()->with('success', 'Laporan disetujui atas nama tindakan koordinator.');
    }

    public function submitDraftOnBehalf(Request $request, KpFinalReport $report, KpFinalReportService $service)
    {
        $this->ensureCoordinator($request);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $service->submitDraftOnBehalf($request->user(), $report, $data['reason']);

        return back()->with('success', 'Draft diajukan untuk review oleh koordinator. Periksa laporan sebelum memberi persetujuan.');
    }

    public function approveGuidanceOnBehalf(Request $request, KpFinalReport $report, KpReportGuidanceLog $guidance, KpFinalReportService $service)
    {
        $this->ensureCoordinator($request);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $service->approveGuidanceOnBehalf($request->user(), $report, $guidance, $data['reason']);

        return back()->with('success', 'Sesi bimbingan divalidasi oleh koordinator.');
    }

    public function completeGuidanceOnBehalf(Request $request, KpFinalReport $report, string $role, KpFinalReportService $service)
    {
        $this->ensureCoordinator($request);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $service->completeGuidanceOnBehalf($request->user(), $report, $role, $data['reason']);

        return back()->with('success', 'Bimbingan ditandai selesai oleh koordinator.');
    }

    private function ensureCoordinator(Request $request): void
    {
        abort_unless($request->session()->get('active_role') === 'koordinator_kp' && $request->user()->hasRole('koordinator_kp'), 403);
    }

    public function download(KpFinalReportFile $file): StreamedResponse
    {
        return Storage::disk($file->file_disk ?: 'local')->download($file->file_path, $file->original_filename);
    }
}
