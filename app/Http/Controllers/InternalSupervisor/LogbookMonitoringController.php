<?php

namespace App\Http\Controllers\InternalSupervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreKpLogbookCommentRequest;
use App\Models\KpLogbook;
use App\Services\KpLogbookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogbookMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $lecturerId = $request->user()->lecturer?->id;
        $logbooks = KpLogbook::query()
            ->with(['assignment.student.user', 'assignment.period', 'assignment.place'])
            ->whereHas('assignment', fn ($q) => $q->where('internal_supervisor_id', $lecturerId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = $request->q;
                $q->whereHas('assignment.student', fn ($student) => $student->where('nim', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%")));
            })
            ->latest('activity_date')
            ->paginate(10)
            ->withQueryString();

        return view('internal-supervisor.logbooks.index', [
            'logbooks' => $logbooks,
            'filters' => $request->only(['status', 'q']),
        ]);
    }

    public function show(KpLogbook $logbook, KpLogbookService $service): View
    {
        $service->ensureInternalSupervisorCanView(request()->user(), $logbook);

        return view('internal-supervisor.logbooks.show', [
            'logbook' => $logbook->load(['assignment.student.user', 'assignment.period', 'assignment.place', 'assignment.fieldSupervisor.user', 'comments.user', 'logs.user']),
        ]);
    }

    public function comments(StoreKpLogbookCommentRequest $request, KpLogbook $logbook, KpLogbookService $service): RedirectResponse
    {
        $service->ensureInternalSupervisorCanView($request->user(), $logbook);
        $service->addComment($request->user(), $logbook, $request->comment, $request->visibility);

        return back()->with('status', 'Komentar pemantauan berhasil ditambahkan.');
    }

    public function download(KpLogbook $logbook, KpLogbookService $service): StreamedResponse
    {
        $service->ensureInternalSupervisorCanView(request()->user(), $logbook);
        abort_unless($logbook->hasEvidence(), 404);

        return Storage::disk($logbook->evidence_disk ?: 'local')->download($logbook->evidence_path, $logbook->evidence_original_filename);
    }
}
