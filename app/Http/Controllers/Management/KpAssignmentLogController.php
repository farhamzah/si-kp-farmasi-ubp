<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpAssignmentLog;
use Illuminate\View\View;

class KpAssignmentLogController extends Controller
{
    public function index(): View
    {
        return view('management.assignment-logs.index', [
            'logs' => KpAssignmentLog::with(['assignment.student.user', 'assignment.place', 'user'])->latest()->paginate(15),
        ]);
    }
}
