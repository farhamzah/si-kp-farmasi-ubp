<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\CancelExamRequest;
use App\Http\Requests\Management\ScheduleExamRequest;
use App\Http\Requests\Management\UpdateExamScheduleRequest;
use App\Models\KpExam;
use App\Models\KpExamInvitationSignatory;
use App\Models\KpExamRequest;
use App\Models\KpPeriod;
use App\Models\Lecturer;
use App\Services\KpExamMinuteService;
use App\Services\KpExamService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ExamScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $exams = $this->filteredExamQuery($request)
            ->latest('exam_date')
            ->paginate(10)
            ->withQueryString();

        return view('management.exams.index', [
            'exams' => $exams,
            'periods' => KpPeriod::latest()->get(),
            'filters' => $request->only(['period', 'status', 'date_from', 'date_to']),
            'signatory' => KpExamInvitationSignatory::active(),
        ]);
    }

    public function reportPreview(Request $request): View
    {
        return view('management.exams.report-preview', $this->reportData($request) + [
            'printMode' => $request->boolean('print'),
        ]);
    }

    public function reportPdf(Request $request): Response
    {
        $pdf = Pdf::loadView('management.exams.report-pdf', $this->reportData($request))
            ->setPaper('a4', 'landscape')
            ->setOption(['defaultFont' => 'DejaVu Sans', 'dpi' => 120, 'isRemoteEnabled' => false]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="jadwal-sidang-kp.pdf"',
        ]);
    }

    public function show(KpExam $exam, KpExamMinuteService $minuteService): View
    {
        $exam->load(['request.logs.user', 'assignment.student.user', 'assignment.period', 'assignment.place', 'assignment.scores', 'supervisor.user', 'examiner.user', 'examiners.user', 'chair.user', 'minutes']);
        if ($exam->minutes) {
            $minuteService->syncReadiness($exam->minutes);
            $exam->load('minutes');
        }
        return view('management.exams.show', compact('exam'));
    }

    public function create(KpExamRequest $examRequest): View|RedirectResponse
    {
        if (! $examRequest->canBeScheduled()) {
            return redirect()
                ->route('management.exam-requests.show', $examRequest)
                ->withErrors(['request' => 'Kandidat harus disetujui koordinator sebelum dijadwalkan.']);
        }

        $examRequest->loadMissing('assignment.finalReport');
        if (! $examRequest->assignment?->isEligibleForExamRequest()) {
            return redirect()
                ->route('management.exam-requests.show', $examRequest)
                ->withErrors(['request' => 'Checklist kesiapan sidang belum lengkap. Validasi akhir perlu diperiksa ulang.']);
        }

        return view('management.exams.schedule', ['examRequest' => $examRequest->load(['assignment.student.user', 'assignment.period', 'assignment.place', 'assignment.internalSupervisor.user', 'assignment.fieldSupervisor.user', 'assignment.finalReport.latestFile']), 'exam' => null, 'examiners' => $this->examiners()]);
    }

    public function store(ScheduleExamRequest $request, KpExamRequest $examRequest, KpExamService $service): RedirectResponse
    {
        $exam = $service->scheduleExam($request->user(), $examRequest, $request->validated());
        return redirect()->route('management.exams.show', $exam)->with('status', 'Sidang berhasil dijadwalkan.');
    }

    public function edit(KpExam $exam): View
    {
        return view('management.exams.schedule', ['examRequest' => $exam->request->load(['assignment.student.user', 'assignment.period', 'assignment.place', 'assignment.internalSupervisor.user', 'assignment.fieldSupervisor.user', 'assignment.finalReport.latestFile']), 'exam' => $exam->load('examiners'), 'examiners' => $this->examiners()]);
    }

    public function update(UpdateExamScheduleRequest $request, KpExam $exam, KpExamService $service): RedirectResponse
    {
        $service->rescheduleExam($request->user(), $exam, $request->validated());
        return redirect()->route('management.exams.show', $exam)->with('status', 'Jadwal sidang berhasil diperbarui.');
    }

    public function cancel(CancelExamRequest $request, KpExam $exam, KpExamService $service): RedirectResponse
    {
        $service->cancelExam($request->user(), $exam, $request->reason);
        return back()->with('status', 'Sidang berhasil dibatalkan.');
    }

    private function examiners()
    {
        return Lecturer::with('user')->whereHas('user.roles', fn ($q) => $q->where('name', 'penguji'))->get()->sortBy(fn (Lecturer $lecturer) => lecturer_display_name($lecturer))->values();
    }

    private function filteredExamQuery(Request $request): Builder
    {
        return KpExam::query()
            ->with(['assignment.student.user', 'assignment.period', 'assignment.place', 'supervisor.user', 'examiner.user', 'examiners.user', 'chair.user', 'invitation', 'minutes'])
            ->when($request->filled('period'), fn (Builder $q) => $q->whereHas('assignment', fn (Builder $a) => $a->where('kp_period_id', $request->integer('period'))))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('exam_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('exam_date', '<=', $request->date('date_to')));
    }

    private function reportData(Request $request): array
    {
        $period = $request->filled('period') ? KpPeriod::find($request->integer('period')) : null;

        return [
            'exams' => $this->filteredExamQuery($request)->orderBy('exam_date')->orderBy('start_time')->get(),
            'filters' => [
                'Periode' => $period?->name ?? 'Semua periode',
                'Status' => $request->filled('status') ? ucfirst((string) $request->status) : 'Semua status',
                'Rentang tanggal' => ($request->date_from ?: 'Awal').' s.d. '.($request->date_to ?: 'Akhir'),
            ],
            'query' => $request->only(['period', 'status', 'date_from', 'date_to']),
            'logoSrc' => $this->fileDataUri(public_path('images/logo-ubp-karawang.png'), 'image/png'),
        ];
    }

    private function fileDataUri(string $path, string $mime): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
