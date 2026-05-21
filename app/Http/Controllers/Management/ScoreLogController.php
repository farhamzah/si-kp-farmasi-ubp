<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpScoreLog;
use Illuminate\View\View;

class ScoreLogController extends Controller
{
    public function index(): View
    {
        return view('management.score-logs.index', [
            'logs' => KpScoreLog::with(['assignment.student.user', 'score.component', 'finalScore', 'user'])->latest()->paginate(20),
        ]);
    }
}
