<?php

namespace App\Http\Controllers\InternalSupervisor;

use App\Http\Controllers\Controller;
use App\Models\KpAssignment;
use App\Models\KpCompetency;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompetencyController extends Controller
{
    public function index(Request $request): View
    {
        $assignments = KpAssignment::query()
            ->with(['period', 'student.user', 'place', 'fieldSupervisor.user', 'competencyAchievements'])
            ->where('internal_supervisor_id', $request->user()->lecturer?->id)
            ->latest()
            ->paginate(10);

        return view('internal-supervisor.competencies.index', ['assignments' => $assignments]);
    }

    public function show(Request $request, KpAssignment $assignment): View
    {
        abort_unless($assignment->internal_supervisor_id === $request->user()->lecturer?->id, 403);

        $assignment->load(['period', 'student.user', 'place', 'fieldSupervisor.user', 'competencyAchievements.checkedBy']);
        $competencies = KpCompetency::query()
            ->where('status', 'aktif')
            ->where(fn ($query) => $query->whereNull('kp_period_id')->orWhere('kp_period_id', $assignment->kp_period_id))
            ->where(fn ($query) => $query->whereNull('place_type')->orWhere('place_type', $assignment->place?->type))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('internal-supervisor.competencies.show', [
            'assignment' => $assignment,
            'competencies' => $competencies,
            'readonly' => true,
        ]);
    }
}
