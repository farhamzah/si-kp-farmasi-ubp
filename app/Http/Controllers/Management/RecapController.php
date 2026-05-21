<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpPeriod;
use App\Services\KpRecapService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecapController extends Controller
{
    public function index(KpRecapService $service): View
    {
        return view('management.recaps.index', ['summary' => $service->summary()]);
    }

    public function students(Request $request, KpRecapService $service): View
    {
        return $this->table('Rekap Mahasiswa KP', 'students', $request, $service);
    }

    public function placements(Request $request, KpRecapService $service): View
    {
        return $this->table('Rekap Penempatan KP', 'placements', $request, $service);
    }

    public function logbooks(Request $request, KpRecapService $service): View
    {
        return $this->table('Rekap Logbook KP', 'logbooks', $request, $service);
    }

    public function exams(Request $request, KpRecapService $service): View
    {
        return $this->table('Rekap Sidang KP', 'exams', $request, $service);
    }

    public function scores(Request $request, KpRecapService $service): View
    {
        return $this->table('Rekap Nilai KP', 'scores', $request, $service);
    }

    private function table(string $title, string $type, Request $request, KpRecapService $service): View
    {
        return view('management.recaps.table', [
            'title' => $title,
            'type' => $type,
            'rows' => $service->rows($type, $request),
            'periods' => KpPeriod::latest()->get(),
            'filters' => $request->only(['period', 'status', 'q']),
        ]);
    }
}
