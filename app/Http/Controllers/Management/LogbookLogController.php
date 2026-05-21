<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpLogbookLog;
use App\Models\KpPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogbookLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = KpLogbookLog::query()
            ->with(['user', 'logbook.assignment.student.user', 'logbook.assignment.period', 'logbook.assignment.place'])
            ->when($request->filled('period'), fn ($q) => $q->whereHas('logbook.assignment', fn ($assignment) => $assignment->where('kp_period_id', $request->period)))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('management.logbook-logs.index', [
            'logs' => $logs,
            'periods' => KpPeriod::latest()->get(),
            'filters' => $request->only(['period', 'action']),
        ]);
    }
}
