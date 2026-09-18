<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpPostExamReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostExamReportController extends Controller
{
    public function index(Request $request): View
    {
        $reports = KpPostExamReport::query()
            ->with(['assignment.student.user', 'assignment.period', 'assignment.place', 'reviewer'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $keyword = $request->string('q')->toString();
                $query->whereHas('assignment.student', fn ($student) => $student
                    ->where('nim', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$keyword}%")));
            })
            ->orderByRaw("CASE status WHEN 'menunggu_validasi' THEN 0 WHEN 'revisi' THEN 1 WHEN 'draft' THEN 2 ELSE 3 END")
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('management.post-exam-reports.index', compact('reports'));
    }

    public function approve(Request $request, KpPostExamReport $report): RedirectResponse
    {
        abort_unless($report->hasDocument(), 422);
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:2000']]);
        $report->update([
            'status' => KpPostExamReport::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'] ?? null,
            'approved_at' => now(),
        ]);

        return back()->with('status', 'Dokumen final pascasidang disetujui. Syarat dokumen untuk nilai dan pendaftaran TA sudah terpenuhi.');
    }

    public function revision(Request $request, KpPostExamReport $report): RedirectResponse
    {
        $data = $request->validate(['review_note' => ['required', 'string', 'max:2000']]);
        $report->update([
            'status' => KpPostExamReport::STATUS_REVISION,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'],
            'approved_at' => null,
        ]);

        return back()->with('status', 'Dokumen dikembalikan kepada mahasiswa untuk diperbaiki.');
    }

    public function preview(KpPostExamReport $report): StreamedResponse
    {
        return $this->fileResponse($report, false);
    }

    public function download(KpPostExamReport $report): StreamedResponse
    {
        return $this->fileResponse($report, true);
    }

    private function fileResponse(KpPostExamReport $report, bool $download): StreamedResponse
    {
        abort_unless($report->file_path, 404);
        $disk = Storage::disk($report->file_disk ?: 'local');

        return $download
            ? $disk->download($report->file_path, $report->documentLabel())
            : $disk->response($report->file_path, $report->documentLabel(), array_filter(['Content-Type' => $report->file_mime]));
    }
}
