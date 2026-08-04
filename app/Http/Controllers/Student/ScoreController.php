<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Support\KpScoreCalculator;
use App\Support\StudentScoreVisibility;
use Illuminate\View\View;

class ScoreController extends Controller
{
    public function show(KpScoreCalculator $calculator, StudentScoreVisibility $visibility): View
    {
        $assignment = request()->user()->student?->assignments()
            ->with(['period.assessmentComponents', 'place', 'scores.component', 'logbooks', 'finalScore', 'finalReport'])
            ->whereIn('status', ['aktif', 'berjalan', 'selesai'])
            ->latest()
            ->first();

        $scoreVisibility = $assignment ? $visibility->resolve($assignment) : null;

        return view('student.scores.show', [
            'assignment' => $assignment,
            'finalScore' => $assignment?->finalScore,
            'breakdown' => $assignment && ($scoreVisibility['visible'] ?? false) ? $calculator->breakdown($assignment) : null,
            'scoreVisibility' => $scoreVisibility,
        ]);
    }
}
