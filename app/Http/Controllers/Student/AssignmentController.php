<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function show(Request $request): View
    {
        $student = $request->user()->student;

        return view('student.assignments.show', [
            'assignment' => $student?->assignments()->with(['period', 'registration', 'selection.place', 'place', 'internalSupervisor.user', 'fieldSupervisor.user'])->latest()->first(),
            'selection' => $student?->placeSelections()->with(['period', 'place'])->where('status', 'aktif')->latest()->first(),
        ]);
    }
}
