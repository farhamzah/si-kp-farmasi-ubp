<?php

namespace App\Http\Controllers;

use App\Models\KpExam;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExamInvitationInboxController extends Controller
{
    public function __invoke(Request $request): View
    {
        $role = (string) $request->session()->get('active_role');
        $query = $this->scopedQuery($request, $role)
            ->with([
                'assignment.student.user',
                'assignment.period',
                'assignment.place',
                'assignment.internalSupervisor.user',
                'assignment.fieldSupervisor.user',
                'supervisor.user',
                'examiner.user',
                'examiners.user',
                'scheduledBy',
            ])
            ->whereIn('status', ['dijadwalkan', 'ditunda'])
            ->orderBy('exam_date')
            ->orderBy('start_time');

        $today = Carbon::today()->toDateString();
        $summaryQuery = $this->scopedQuery($request, $role)->whereIn('status', ['dijadwalkan', 'ditunda']);

        return view('exam-invitations.index', [
            'activeRole' => $role,
            'exams' => $query->paginate(10)->withQueryString(),
            'summary' => [
                'total' => (clone $summaryQuery)->count(),
                'today' => (clone $summaryQuery)->whereDate('exam_date', $today)->count(),
                'upcoming' => (clone $summaryQuery)->whereDate('exam_date', '>=', $today)->where('status', 'dijadwalkan')->count(),
            ],
        ]);
    }

    private function scopedQuery(Request $request, string $role): Builder
    {
        $query = KpExam::query();
        $user = $request->user();

        return match ($role) {
            'admin', 'koordinator_kp' => $query,
            'mahasiswa' => $query->whereHas('assignment.student', fn (Builder $student) => $student->where('user_id', $user->id)),
            'pembimbing_dalam' => $query->where('supervisor_id', $user->lecturer?->id ?: 0),
            'pembimbing_lapangan' => $query->whereHas('assignment', fn (Builder $assignment) => $assignment->where('field_supervisor_id', $user->fieldSupervisor?->id ?: 0)),
            'penguji' => $query->forExaminer($user->lecturer?->id),
            default => abort(403),
        };
    }
}
