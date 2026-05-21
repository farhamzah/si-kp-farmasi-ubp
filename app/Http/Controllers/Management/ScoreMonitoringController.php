<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\FinalizeScoreRequest;
use App\Http\Requests\Management\UnlockScoreRequest;
use App\Models\KpAssignment;
use App\Models\KpFinalScore;
use App\Models\KpPeriod;
use App\Services\KpAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScoreMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $assignments = KpAssignment::with(['period', 'student.user', 'place', 'internalSupervisor.user', 'fieldSupervisor.user', 'exam.examiner.user', 'scores.component', 'finalScore'])
            ->when($request->filled('period'), fn ($q) => $q->where('kp_period_id', $request->period))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('student', fn ($s) => $s->where('nim', 'like', "%{$request->q}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$request->q}%"))))
            ->latest()
            ->paginate(10)->withQueryString();

        return view('management.scores.index', ['assignments' => $assignments, 'periods' => KpPeriod::latest()->get(), 'filters' => $request->only(['period', 'q'])]);
    }

    public function show(KpAssignment $assignment): View
    {
        return view('management.scores.show', ['assignment' => $assignment->load(['period.assessmentComponents', 'student.user', 'place', 'internalSupervisor.user', 'fieldSupervisor.user', 'exam.examiner.user', 'scores.component', 'finalScore'])]);
    }

    public function calculate(KpAssignment $assignment, KpAssessmentService $service): RedirectResponse
    {
        $service->calculateFinalScore($assignment);
        return back()->with('status', 'Nilai akhir berhasil dihitung.');
    }

    public function finalize(FinalizeScoreRequest $request, KpAssignment $assignment, KpAssessmentService $service): RedirectResponse
    {
        $service->finalizeScore($request->user(), $assignment, $request->note);
        return back()->with('status', 'Nilai akhir berhasil dikunci.');
    }

    public function publish(KpFinalScore $finalScore, KpAssessmentService $service): RedirectResponse
    {
        $service->publishScore(request()->user(), $finalScore);
        return back()->with('status', 'Nilai akhir berhasil dipublish.');
    }

    public function unlock(UnlockScoreRequest $request, KpFinalScore $finalScore, KpAssessmentService $service): RedirectResponse
    {
        $service->unlockScore($request->user(), $finalScore, $request->reason);
        return back()->with('status', 'Nilai akhir berhasil dibuka kembali.');
    }
}
