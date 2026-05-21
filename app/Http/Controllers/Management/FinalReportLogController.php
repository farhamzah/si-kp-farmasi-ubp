<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpFinalReportLog;
use App\Models\KpPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinalReportLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = KpFinalReportLog::query()
            ->with(['user', 'report.assignment.student.user', 'report.assignment.period', 'report.assignment.place'])
            ->when($request->filled('period'), fn ($q) => $q->whereHas('report.assignment', fn ($assignment) => $assignment->where('kp_period_id', $request->period)))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('management.final-report-logs.index', [
            'logs' => $logs,
            'periods' => KpPeriod::latest()->get(),
            'filters' => $request->only(['period', 'action']),
        ]);
    }
}
