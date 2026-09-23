<?php

namespace App\Http\Controllers;

use App\Models\KpExam;
use App\Models\KpExamLog;
use App\Models\KpExamMinute;
use App\Services\KpExamMinuteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamMinuteController extends Controller
{
    public function close(Request $request, KpExam $exam, KpExamMinuteService $service): RedirectResponse
    {
        abort_unless(in_array($request->session()->get('active_role'), ['pembimbing_dalam', 'penguji'], true), 403);
        $data = $request->validate([
            'result' => ['required', Rule::in(['lulus', 'lulus_revisi', 'belum_lulus'])],
            'actual_start_time' => ['required', 'date_format:H:i'],
            'actual_end_time' => ['required', 'date_format:H:i', 'after:actual_start_time'],
            'revision_deadline' => ['nullable', 'required_if:result,lulus_revisi', 'date'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'attendance' => ['nullable', 'array'],
            'attendance.*' => ['string', 'max:80'],
        ]);
        $service->close($exam, $request->user(), $data);

        return back()->with('status', 'Sidang ditutup dan berita acara berhasil dibuat.');
    }

    public function publish(Request $request, KpExamMinute $minute, KpExamMinuteService $service): RedirectResponse
    {
        abort_unless(in_array($request->session()->get('active_role'), ['admin', 'koordinator_kp'], true), 403);
        $service->publish($minute, $request->user());
        return back()->with('status', 'Berita acara berhasil diterbitkan.');
    }

    public function preview(Request $request, KpExamMinute $minute, KpExamMinuteService $service): View
    {
        $this->authorizeAccess($request, $minute);
        $minute = $service->syncReadiness($minute);
        return view('exam-minutes.document', ['minute' => $minute->load(array_merge(['chair.user'], array_map(fn ($r) => 'exam.'.$r, $service->relations()))), 'verificationUrl' => $service->verificationUrl($minute)]);
    }

    public function downloadPdf(Request $request, KpExamMinute $minute, KpExamMinuteService $service): Response
    {
        $this->authorizeAccess($request, $minute);
        return $service->pdfResponse($service->syncReadiness($minute));
    }

    public function verify(string $code): View
    {
        $minute = KpExamMinute::with(['exam.assignment.student.user', 'exam.assignment.place', 'chair.user'])->where('verification_code', $code)->first();
        $revokedCorrection = null;
        if (! $minute) {
            $revokedCorrection = KpExamLog::with(['exam.assignment.student.user'])
                ->where('action', 'examiner_assignment_corrected')
                ->where('metadata->revoked_minute->verification_code', $code)
                ->latest()
                ->first();
        }

        return view('exam-minutes.verify', compact('minute', 'revokedCorrection'));
    }

    public function qr(KpExamMinute $minute, KpExamMinuteService $service): Response
    {
        return response($service->qrSvg($minute), 200, ['Content-Type' => 'image/svg+xml']);
    }

    private function authorizeAccess(Request $request, KpExamMinute $minute): void
    {
        $role = (string) $request->session()->get('active_role');
        $user = $request->user();
        $exam = $minute->exam;
        $allowed = match ($role) {
            'admin', 'koordinator_kp' => true,
            'mahasiswa' => $exam->assignment()->whereHas('student', fn (Builder $q) => $q->where('user_id', $user->id))->exists(),
            'pembimbing_dalam' => (int) $exam->supervisor_id === (int) ($user->lecturer?->id ?: 0),
            'pembimbing_lapangan' => $exam->assignment()->where('field_supervisor_id', $user->fieldSupervisor?->id ?: 0)->exists(),
            'penguji' => $exam->hasExaminer($user->lecturer?->id),
            default => false,
        };
        abort_unless($allowed, 403);
    }
}
