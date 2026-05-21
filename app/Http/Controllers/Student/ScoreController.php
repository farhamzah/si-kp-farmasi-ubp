<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ScoreController extends Controller
{
    public function show(): View
    {
        $assignment = request()->user()->student?->assignments()
            ->with(['period', 'place', 'scores.component', 'finalScore'])
            ->whereIn('status', ['aktif', 'berjalan', 'selesai'])
            ->latest()
            ->first();

        return view('student.scores.show', ['assignment' => $assignment, 'finalScore' => $assignment?->finalScore]);
    }
}
