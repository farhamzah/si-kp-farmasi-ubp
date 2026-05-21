<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpExamLog;
use App\Models\KpPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = KpExamLog::query()
            ->with(['user', 'request.assignment.student.user', 'exam.assignment.place'])
            ->when($request->filled('period'), fn ($q) => $q->whereHas('request.assignment', fn ($a) => $a->where('kp_period_id', $request->period)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('management.exam-logs.index', ['logs' => $logs, 'periods' => KpPeriod::latest()->get(), 'filters' => $request->only(['period'])]);
    }
}
