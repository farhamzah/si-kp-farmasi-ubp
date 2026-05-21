<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\KpPeriod;
use App\Models\KpSelectionLog;
use App\Models\KpWaitingList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaitingListController extends Controller
{
    public function index(Request $request): View
    {
        $waitingLists = KpWaitingList::query()
            ->with(['period', 'student.user', 'registration'])
            ->when($request->filled('period'), fn ($query) => $query->where('kp_period_id', $request->period))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = $request->q;
                $query->whereHas('student', fn ($student) => $student->where('nim', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%")));
            })
            ->latest('joined_at')
            ->paginate(10)
            ->withQueryString();

        return view('management.waiting-lists.index', [
            'waitingLists' => $waitingLists,
            'periods' => KpPeriod::latest()->get(),
            'filters' => $request->only(['period', 'status', 'q']),
        ]);
    }

    public function cancel(Request $request, KpWaitingList $waitingList): RedirectResponse
    {
        $waitingList->update(['status' => 'dibatalkan', 'resolved_at' => now(), 'note' => $request->input('note', 'Dibatalkan oleh Admin/Koordinator.')]);
        KpSelectionLog::create([
            'kp_period_id' => $waitingList->kp_period_id,
            'kp_registration_id' => $waitingList->kp_registration_id,
            'student_id' => $waitingList->student_id,
            'user_id' => $request->user()->id,
            'action' => 'waiting_list_removed',
            'status' => 'info',
            'message' => $waitingList->note,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'Daftar tunggu berhasil dibatalkan.');
    }
}
