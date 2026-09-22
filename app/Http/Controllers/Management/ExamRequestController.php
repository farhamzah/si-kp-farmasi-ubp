<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\ReviewExamRequestRequest;
use App\Models\KpAssignment;
use App\Models\KpExamRequest;
use App\Models\KpPeriod;
use App\Services\KpExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamRequestController extends Controller
{
    public function index(Request $request): View
    {
        $candidates = KpAssignment::query()
            ->with(['student.user', 'period', 'place', 'finalReport'])
            ->whereIn('status', ['aktif', 'berjalan', 'selesai'])
            ->whereDoesntHave('examRequest')
            ->whereHas('finalReport', fn ($query) => $query
                ->where(fn ($report) => $report->whereHas('files')
                    ->orWhere(fn ($document) => $document->whereNotNull('final_document_url')->where('final_document_url', '!=', ''))))
            ->when($request->filled('period'), fn ($query) => $query->where('kp_period_id', $request->integer('period')))
            ->when($request->filled('q'), fn ($query) => $query->whereHas('student', fn ($student) => $student
                ->where('nim', 'like', '%'.$request->q.'%')
                ->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$request->q.'%'))))
            ->latest('id')
            ->get()
            ->filter(fn (KpAssignment $assignment) => $assignment->isEligibleForExamRequest());

        $summary = [
            'diajukan' => KpExamRequest::where('status', 'diajukan')->count(),
            'disetujui' => KpExamRequest::where('status', 'disetujui')->count(),
            'dijadwalkan' => KpExamRequest::where('status', 'dijadwalkan')->count(),
            'revisi' => KpExamRequest::where('status', 'revisi')->count(),
        ];

        $requests = KpExamRequest::query()
            ->with([
                'assignment.student.user',
                'assignment.period',
                'assignment.place',
                'assignment.internalSupervisor.user',
                'assignment.fieldSupervisor.user',
                'assignment.finalReport.latestFile',
                'exam',
            ])
            ->when($request->filled('period'), fn ($q) => $q->whereHas('assignment', fn ($a) => $a->where('kp_period_id', $request->period)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status === 'siap_diajukan' ? '__none__' : $request->status))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('assignment.student', fn ($s) => $s->where('nim', 'like', "%{$request->q}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$request->q}%"))))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('management.exam-requests.index', [
            'requests' => $requests,
            'periods' => KpPeriod::latest()->get(),
            'filters' => $request->only(['period', 'status', 'q']),
            'summary' => $summary,
            'candidates' => $candidates,
        ]);
    }

    public function enqueue(Request $request, KpAssignment $assignment, KpExamService $service): RedirectResponse
    {
        $examRequest = $service->submitForCoordinator($request->user(), $assignment);

        return redirect()->route('management.exam-requests.show', $examRequest)
            ->with('status', 'Kandidat masuk antrean. Validasi pengajuan sebelum menjadwalkan sidang.');
    }

    public function show(KpExamRequest $examRequest): View
    {
        return view('management.exam-requests.show', ['examRequest' => $examRequest->load(['assignment.student.user', 'assignment.period', 'assignment.place', 'assignment.internalSupervisor.user', 'assignment.fieldSupervisor.user', 'assignment.finalReport.latestFile', 'exam.examiner.user', 'exam.examiners.user', 'logs.user'])]);
    }

    public function approve(ReviewExamRequestRequest $request, KpExamRequest $examRequest, KpExamService $service): RedirectResponse
    {
        $service->approveRequest($request->user(), $examRequest, $request->review_note);
        return back()->with('status', 'Pengajuan sidang berhasil disetujui.');
    }

    public function revision(ReviewExamRequestRequest $request, KpExamRequest $examRequest, KpExamService $service): RedirectResponse
    {
        if (! $request->filled('review_note')) {
            throw ValidationException::withMessages(['review_note' => 'Catatan revisi wajib diisi.']);
        }
        $service->requestRevision($request->user(), $examRequest, $request->review_note);
        return back()->with('status', 'Revisi pengajuan sidang berhasil diminta.');
    }

    public function reject(ReviewExamRequestRequest $request, KpExamRequest $examRequest, KpExamService $service): RedirectResponse
    {
        if (! $request->filled('review_note')) {
            throw ValidationException::withMessages(['review_note' => 'Catatan penolakan wajib diisi.']);
        }
        $service->rejectRequest($request->user(), $examRequest, $request->review_note);
        return back()->with('status', 'Pengajuan sidang berhasil ditolak.');
    }

    public function approvePaymentProof(Request $request, KpExamRequest $examRequest, KpExamService $service): RedirectResponse
    {
        $request->validate(['payment_proof_review_note' => ['nullable', 'string', 'max:1000']]);
        $service->approvePaymentProof($request->user(), $examRequest, $request->input('payment_proof_review_note'));

        return back()->with('status', 'Bukti pembayaran berhasil disetujui.');
    }

    public function revisionPaymentProof(Request $request, KpExamRequest $examRequest, KpExamService $service): RedirectResponse
    {
        $validated = $request->validate([
            'payment_proof_review_note' => ['required', 'string', 'max:1000'],
        ], [
            'payment_proof_review_note.required' => 'Catatan pengembalian bukti pembayaran wajib diisi.',
        ]);

        $service->requestPaymentProofRevision($request->user(), $examRequest, $validated['payment_proof_review_note']);

        return back()->with('status', 'Bukti pembayaran dikembalikan ke mahasiswa untuk diganti.');
    }

    public function previewPaymentProof(KpExamRequest $examRequest): StreamedResponse
    {
        abort_unless($examRequest->payment_proof_path, 404);

        return Storage::disk($examRequest->payment_proof_disk ?: 'local')->response(
            $examRequest->payment_proof_path,
            $examRequest->paymentProofLabel(),
            array_filter(['Content-Type' => $examRequest->payment_proof_mime]),
            'inline'
        );
    }

    public function downloadPaymentProof(KpExamRequest $examRequest): StreamedResponse
    {
        abort_unless($examRequest->payment_proof_path, 404);

        return Storage::disk($examRequest->payment_proof_disk ?: 'local')->download(
            $examRequest->payment_proof_path,
            $examRequest->paymentProofLabel()
        );
    }
}
