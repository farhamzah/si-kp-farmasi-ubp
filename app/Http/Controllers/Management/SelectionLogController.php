<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpPeriod;
use App\Models\KpSelectionLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SelectionLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = KpSelectionLog::query()
            ->with(['period', 'student.user', 'place', 'user'])
            ->when($request->filled('period'), fn ($query) => $query->where('kp_period_id', $request->period))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('q'), fn ($query) => $query->where('action', 'like', '%'.$request->q.'%')->orWhere('message', 'like', '%'.$request->q.'%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('management.selection-logs.index', [
            'logs' => $logs,
            'periods' => KpPeriod::latest()->get(),
            'filters' => $request->only(['period', 'status', 'q']),
        ]);
    }
}
