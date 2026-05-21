<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpPeriod;
use App\Models\KpPlace;
use App\Models\KpQuotaLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KpQuotaLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = KpQuotaLog::query()
            ->with(['user', 'quota.period', 'quota.place'])
            ->when($request->filled('period'), fn ($query) => $query->whereHas('quota', fn ($quota) => $quota->where('kp_period_id', $request->period)))
            ->when($request->filled('place'), fn ($query) => $query->whereHas('quota', fn ($quota) => $quota->where('kp_place_id', $request->place)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('management.quota-logs.index', [
            'logs' => $logs,
            'periods' => KpPeriod::latest()->get(),
            'places' => KpPlace::orderBy('name')->get(),
            'filters' => $request->only(['period', 'place']),
        ]);
    }
}
