<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitExamRequestRequest;
use App\Models\KpAssignment;
use App\Services\KpExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamRequestController extends Controller
{
    public function index(): View
    {
        $assignment = $this->activeAssignment();

        return view('student.exams.index', [
            'assignment' => $assignment?->load(['place', 'internalSupervisor.user', 'finalReport', 'examRequest.exam.examiner.user', 'examRequest.exam.examiners.user', 'examRequest.exam.supervisor.user']),
            'examRequest' => $assignment?->examRequest,
            'exam' => $assignment?->exam,
            'examEligibility' => $assignment?->examEligibility(),
        ]);
    }

    public function submit(SubmitExamRequestRequest $request, KpExamService $service): RedirectResponse
    {
        $service->submitRequest($request->user(), $this->activeAssignmentOrFail(), $request->request_note, [
            'file' => $request->file('payment_proof'),
            'url' => $request->input('payment_proof_url'),
            'label' => $request->input('payment_proof_label'),
        ]);

        return back()->with('status', 'Pengajuan sidang berhasil dikirim.');
    }

    public function cancel(): RedirectResponse
    {
        $assignment = $this->activeAssignmentOrFail();
        $examRequest = $assignment->examRequest;
        abort_unless($examRequest && in_array($examRequest->status, ['draft', 'diajukan', 'revisi'], true), 403);
        app(KpExamService::class)->cancelRequest(request()->user(), $examRequest, 'Dibatalkan oleh mahasiswa.');

        return back()->with('status', 'Pengajuan sidang berhasil dibatalkan.');
    }

    public function updatePaymentProof(SubmitExamRequestRequest $request, KpExamService $service): RedirectResponse
    {
        $assignment = $this->activeAssignmentOrFail();
        abort_unless($assignment->examRequest, 404);

        $service->replacePaymentProof($request->user(), $assignment->examRequest, [
            'file' => $request->file('payment_proof'),
            'url' => $request->input('payment_proof_url'),
            'label' => $request->input('payment_proof_label'),
        ]);

        return back()->with('status', 'Bukti pembayaran berhasil diganti dan menunggu validasi koordinator.');
    }

    public function previewPaymentProof(): StreamedResponse
    {
        $examRequest = $this->activeAssignmentOrFail()->examRequest;
        abort_unless($examRequest?->payment_proof_path, 404);

        return Storage::disk($examRequest->payment_proof_disk ?: 'local')->response(
            $examRequest->payment_proof_path,
            $examRequest->paymentProofLabel(),
            array_filter(['Content-Type' => $examRequest->payment_proof_mime]),
            'inline'
        );
    }

    public function downloadPaymentProof(): StreamedResponse
    {
        $examRequest = $this->activeAssignmentOrFail()->examRequest;
        abort_unless($examRequest?->payment_proof_path, 404);

        return Storage::disk($examRequest->payment_proof_disk ?: 'local')->download(
            $examRequest->payment_proof_path,
            $examRequest->paymentProofLabel()
        );
    }

    private function activeAssignment(): ?KpAssignment
    {
        return request()->user()->student?->assignments()->whereIn('status', ['aktif', 'berjalan'])->latest()->first();
    }

    private function activeAssignmentOrFail(): KpAssignment
    {
        $assignment = $this->activeAssignment();
        abort_unless($assignment, 403, 'Anda belum memiliki penempatan KP aktif.');

        return $assignment;
    }
}
