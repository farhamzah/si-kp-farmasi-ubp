<?php

namespace App\Http\Controllers\InternalSupervisor;

use App\Http\Controllers\Controller;
use App\Models\KpAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisedStudentController extends Controller
{
    public function index(Request $request): View
    {
        $lecturer = $request->user()->lecturer;
        $assignments = KpAssignment::with(['student.user', 'period', 'place', 'fieldSupervisor.user'])
            ->where('internal_supervisor_id', $lecturer?->id)
            ->paginate(10);

        return view('internal-supervisor.assignments.index', compact('assignments'));
    }

    public function show(Request $request, KpAssignment $assignment): View
    {
        abort_unless($request->user()->lecturer?->id === $assignment->internal_supervisor_id, 403);

        return view('internal-supervisor.assignments.show', [
            'assignment' => $assignment->load(['student.user', 'period', 'place', 'fieldSupervisor.user', 'registration', 'selection']),
        ]);
    }
}
