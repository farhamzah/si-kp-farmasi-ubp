<?php

namespace App\Http\Controllers\FieldSupervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\ReviewKpLogbookRequest;
use App\Models\KpLogbook;
use App\Services\KpLogbookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogbookValidationController extends Controller
{
    public function index(Request $request): View
    {
        $fieldSupervisorId = $request->user()->fieldSupervisor?->id;
        $logbooks = KpLogbook::query()
            ->with(['assignment.student.user', 'assignment.period', 'assignment.place'])
            ->whereHas('assignment', fn ($q) => $q->where('field_supervisor_id', $fieldSupervisorId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = $request->q;
                $q->whereHas('assignment.student', fn ($student) => $student->where('nim', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%")));
            })
            ->latest('activity_date')
            ->paginate(10)
            ->withQueryString();

        return view('field-supervisor.logbooks.index', [
            'logbooks' => $logbooks,
            'filters' => $request->only(['status', 'q']),
        ]);
    }

    public function show(KpLogbook $logbook, KpLogbookService $service): View
    {
        $service->ensureFieldSupervisorCanReview(request()->user(), $logbook);

        return view('field-supervisor.logbooks.show', [
            'logbook' => $logbook->load(['assignment.student.user', 'assignment.period', 'assignment.place', 'comments.user', 'logs.user']),
        ]);
    }

    public function approve(ReviewKpLogbookRequest $request, KpLogbook $logbook, KpLogbookService $service): RedirectResponse
    {
        $service->approve($request->user(), $logbook, $request->validation_note);

        return back()->with('status', 'Logbook berhasil disetujui.');
    }

    public function revision(ReviewKpLogbookRequest $request, KpLogbook $logbook, KpLogbookService $service): RedirectResponse
    {
        if (! $request->filled('validation_note')) {
            throw ValidationException::withMessages(['validation_note' => 'Catatan revisi wajib diisi.']);
        }

        $service->requestRevision($request->user(), $logbook, $request->validation_note);

        return back()->with('status', 'Revisi logbook berhasil diminta.');
    }

    public function reject(ReviewKpLogbookRequest $request, KpLogbook $logbook, KpLogbookService $service): RedirectResponse
    {
        if (! $request->filled('validation_note')) {
            throw ValidationException::withMessages(['validation_note' => 'Catatan penolakan wajib diisi.']);
        }

        $service->reject($request->user(), $logbook, $request->validation_note);

        return back()->with('status', 'Logbook berhasil ditolak.');
    }

    public function download(KpLogbook $logbook, KpLogbookService $service): StreamedResponse
    {
        $service->ensureFieldSupervisorCanReview(request()->user(), $logbook);
        abort_unless($logbook->hasEvidence(), 404);

        return Storage::disk($logbook->evidence_disk ?: 'local')->download($logbook->evidence_path, $logbook->evidence_original_filename);
    }
}
