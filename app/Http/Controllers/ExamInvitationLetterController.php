<?php

namespace App\Http\Controllers;

use App\Models\KpExam;
use App\Models\KpExamInvitation;
use App\Services\KpExamInvitationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExamInvitationLetterController extends Controller
{
    public function store(Request $request, KpExam $exam, KpExamInvitationService $service): RedirectResponse
    {
        abort_unless(in_array($request->session()->get('active_role'), ['admin', 'koordinator_kp'], true), 403);

        try {
            $service->createOrUpdate($exam, $request->user());
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['signatory' => $exception->getMessage()]);
        }

        return back()->with('status', 'Undangan sidang berhasil diterbitkan.');
    }

    public function bulkStore(Request $request, KpExamInvitationService $service): RedirectResponse
    {
        abort_unless(in_array($request->session()->get('active_role'), ['admin', 'koordinator_kp'], true), 403);

        $data = $request->validate([
            'exam_ids' => ['required', 'array', 'min:1'],
            'exam_ids.*' => ['integer', 'exists:kp_exams,id'],
        ]);

        try {
            $signatory = $service->activeSignatory();
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['signatory' => $exception->getMessage()]);
        }

        $count = 0;
        KpExam::query()
            ->whereIn('id', $data['exam_ids'])
            ->doesntHave('invitation')
            ->with(['assignment.student.user', 'assignment.period', 'assignment.place', 'supervisor.user', 'examiner.user', 'examiners.user'])
            ->get()
            ->each(function (KpExam $exam) use ($request, $service, $signatory, &$count): void {
                $service->createOrUpdate($exam, $request->user(), $signatory);
                $count++;
            });

        return back()->with('status', $count.' undangan sidang berhasil diterbitkan.');
    }

    public function updateSignatory(Request $request, KpExamInvitationService $service): RedirectResponse
    {
        abort_unless(in_array($request->session()->get('active_role'), ['admin', 'koordinator_kp'], true), 403);

        $data = $request->validate([
            'coordinator_name' => ['required', 'string', 'max:120'],
            'coordinator_nuptk' => ['nullable', 'string', 'max:80'],
            'head_program_name' => ['required', 'string', 'max:120'],
            'head_program_nuptk' => ['nullable', 'string', 'max:80'],
            'dean_name' => ['required', 'string', 'max:120'],
            'dean_nuptk' => ['nullable', 'string', 'max:80'],
            'effective_start_date' => ['nullable', 'date'],
        ]);

        $service->saveActiveSignatory($data, $request->user());

        return back()->with('status', 'Pejabat penandatangan undangan sidang berhasil disimpan.');
    }

    public function preview(Request $request, KpExamInvitation $invitation, KpExamInvitationService $service): View
    {
        $this->authorizeInvitationAccess($request, $invitation);

        return view('exam-invitations.letter', [
            'invitation' => $invitation->load([
                'exam.assignment.student.user',
                'exam.assignment.period',
                'exam.assignment.place',
                'exam.assignment.fieldSupervisor.user',
                'exam.supervisor.user',
                'exam.examiner.user',
                'exam.examiners.user',
            ]),
            'verificationUrl' => $service->verificationUrl($invitation),
        ]);
    }

    public function downloadPdf(Request $request, KpExamInvitation $invitation, KpExamInvitationService $service): Response
    {
        $this->authorizeInvitationAccess($request, $invitation);

        return $service->pdfResponse($invitation->load(['exam.assignment.student.user', 'exam.assignment.place', 'exam.supervisor.user', 'exam.examiner.user', 'exam.examiners.user']));
    }

    public function downloadWord(Request $request, KpExamInvitation $invitation, KpExamInvitationService $service): Response
    {
        abort_unless(in_array($request->session()->get('active_role'), ['admin', 'koordinator_kp'], true), 403);

        return $service->wordResponse($invitation->load(['exam.assignment.student.user', 'exam.assignment.period', 'exam.assignment.place', 'exam.assignment.fieldSupervisor.user', 'exam.supervisor.user', 'exam.examiner.user', 'exam.examiners.user']));
    }

    public function qr(KpExamInvitation $invitation, KpExamInvitationService $service): Response
    {
        return response($service->qrSvg($invitation), 200, ['Content-Type' => 'image/svg+xml']);
    }

    public function verify(string $code): View
    {
        $invitation = KpExamInvitation::with([
            'exam.assignment.student.user',
            'exam.assignment.period',
            'exam.assignment.place',
            'exam.supervisor.user',
            'exam.examiner.user',
            'exam.examiners.user',
        ])->where('verification_code', $code)->first();

        return view('exam-invitations.verify', ['invitation' => $invitation]);
    }

    private function authorizeInvitationAccess(Request $request, KpExamInvitation $invitation): void
    {
        $role = (string) $request->session()->get('active_role');
        $user = $request->user();
        $exam = $invitation->exam()->firstOrFail();

        $allowed = match ($role) {
            'admin', 'koordinator_kp' => true,
            'mahasiswa' => $exam->assignment()->whereHas('student', fn (Builder $student) => $student->where('user_id', $user->id))->exists(),
            'pembimbing_dalam' => (int) $exam->supervisor_id === (int) ($user->lecturer?->id ?: 0),
            'pembimbing_lapangan' => $exam->assignment()->where('field_supervisor_id', $user->fieldSupervisor?->id ?: 0)->exists(),
            'penguji' => $exam->hasExaminer($user->lecturer?->id),
            default => false,
        };

        abort_unless($allowed, 403);
    }
}
