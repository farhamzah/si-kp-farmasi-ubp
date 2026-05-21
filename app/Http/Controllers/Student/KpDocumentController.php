<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\KpPeriod;
use Illuminate\View\View;

class KpDocumentController extends Controller
{
    public function index(): View
    {
        $student = request()->user()->student;
        $registration = $student?->kpRegistrations()
            ->with(['period.documentRequirements' => fn ($query) => $query->where('status', 'aktif')->orderBy('sort_order')->orderBy('name'), 'documents.requirement'])
            ->latest()
            ->first();

        $openPeriods = KpPeriod::query()
            ->where('status', 'dibuka')
            ->where(function ($query) {
                $query->whereNull('registration_start_at')->orWhere('registration_start_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('registration_end_at')->orWhere('registration_end_at', '>=', now());
            })
            ->latest()
            ->get();

        return view('student.documents.index', [
            'registration' => $registration,
            'openPeriods' => $openPeriods,
        ]);
    }
}
