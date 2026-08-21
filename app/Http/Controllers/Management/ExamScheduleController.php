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
use App\Services\KpExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $exams = KpExam::query()
            ->with(['assignment.student.user', 'assignment.period', 'assignment.place', 'supervisor.user', 'examiner.user', 'examiners.user', 'invitation'])
            ->when($request->filled('period'), fn ($q) => $q->whereHas('assignment', fn ($a) => $a->where('kp_period_id', $request->period)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('exam_date')
            ->paginate(10)
            ->withQueryString();

        return view('management.exams.index', [
            'exams' => $exams,
            'periods' => KpPeriod::latest()->get(),
            'filters' => $request->only(['period', 'status']),
            'signatory' => KpExamInvitationSignatory::active(),
        ]);
    }

    public function show(KpExam $exam): View
    {
        return view('management.exams.show', ['exam' => $exam->load(['request.logs.user', 'assignment.student.user', 'assignment.place', 'supervisor.user', 'examiner.user', 'examiners.user'])]);
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

    public function complete(Request $request, KpExam $exam, KpExamService $service): RedirectResponse
    {
        $service->completeExam($request->user(), $exam, $request->input('note'));
        return back()->with('status', 'Sidang ditandai selesai.');
    }

    private function examiners()
    {
        return Lecturer::with('user')->whereHas('user.roles', fn ($q) => $q->where('name', 'penguji'))->get()->sortBy(fn (Lecturer $lecturer) => lecturer_display_name($lecturer))->values();
    }
}
