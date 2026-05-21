<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\AssignFieldSupervisorRequest;
use App\Http\Requests\Management\AssignInternalSupervisorRequest;
use App\Http\Requests\Management\CancelKpAssignmentRequest;
use App\Http\Requests\Management\StoreKpAssignmentRequest;
use App\Http\Requests\Management\UpdateKpAssignmentRequest;
use App\Models\FieldSupervisor;
use App\Models\KpAssignment;
use App\Models\KpPeriod;
use App\Models\KpPlaceSelection;
use App\Models\Lecturer;
use App\Services\KpAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KpAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $assignments = KpAssignment::query()
            ->with(['period', 'student.user', 'place', 'internalSupervisor.user', 'fieldSupervisor.user'])
            ->when($request->filled('period'), fn ($q) => $q->where('kp_period_id', $request->period))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = $request->q;
                $q->whereHas('student', fn ($student) => $student->where('nim', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%")));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('management.assignments.index', [
            'assignments' => $assignments,
            'periods' => KpPeriod::latest()->get(),
            'filters' => $request->only(['period', 'status', 'q']),
        ]);
    }

    public function create(): View
    {
        return view('management.assignments.create', $this->formData());
    }

    public function store(StoreKpAssignmentRequest $request, KpAssignmentService $service): RedirectResponse
    {
        $assignment = $service->createFromSelection(
            $request->user(),
            KpPlaceSelection::findOrFail($request->kp_place_selection_id),
            $request->integer('internal_supervisor_id') ?: null,
            $request->integer('field_supervisor_id') ?: null,
            $request->note
        );

        return redirect()->route('management.kp-assignments.show', $assignment)->with('status', 'Penempatan KP berhasil dibuat.');
    }

    public function createFromSelection(Request $request, KpPlaceSelection $selection, KpAssignmentService $service): RedirectResponse
    {
        $assignment = $service->createFromSelection($request->user(), $selection);

        return redirect()->route('management.kp-assignments.edit', $assignment)->with('status', 'Penempatan dibuat. Silakan lengkapi pembimbing.');
    }

    public function show(KpAssignment $kpAssignment): View
    {
        return view('management.assignments.show', [
            'assignment' => $kpAssignment->load(['period', 'registration', 'selection', 'student.user', 'place', 'internalSupervisor.user', 'fieldSupervisor.user', 'logs.user']),
        ]);
    }

    public function edit(KpAssignment $kpAssignment): View
    {
        return view('management.assignments.edit', $this->formData() + ['assignment' => $kpAssignment]);
    }

    public function update(UpdateKpAssignmentRequest $request, KpAssignment $kpAssignment, KpAssignmentService $service): RedirectResponse
    {
        $service->updateSupervisors(
            $request->user(),
            $kpAssignment,
            $request->filled('internal_supervisor_id') ? Lecturer::findOrFail($request->internal_supervisor_id) : null,
            $request->filled('field_supervisor_id') ? FieldSupervisor::findOrFail($request->field_supervisor_id) : null,
            $request->note
        );

        return redirect()->route('management.kp-assignments.show', $kpAssignment)->with('status', 'Pembimbing penempatan berhasil diperbarui.');
    }

    public function assignInternalSupervisor(AssignInternalSupervisorRequest $request, KpAssignment $assignment, KpAssignmentService $service): RedirectResponse
    {
        $service->assignInternalSupervisor($request->user(), $assignment, Lecturer::findOrFail($request->internal_supervisor_id), $request->note);

        return back()->with('status', 'Pembimbing dalam berhasil ditetapkan.');
    }

    public function assignFieldSupervisor(AssignFieldSupervisorRequest $request, KpAssignment $assignment, KpAssignmentService $service): RedirectResponse
    {
        $service->assignFieldSupervisor($request->user(), $assignment, FieldSupervisor::findOrFail($request->field_supervisor_id), $request->note);

        return back()->with('status', 'Pembimbing lapangan berhasil ditetapkan.');
    }

    public function cancel(CancelKpAssignmentRequest $request, KpAssignment $assignment, KpAssignmentService $service): RedirectResponse
    {
        $service->cancelAssignment($request->user(), $assignment, $request->reason);

        return back()->with('status', 'Penempatan KP berhasil dibatalkan.');
    }

    private function formData(): array
    {
        return [
            'selections' => KpPlaceSelection::query()
                ->with(['period', 'student.user', 'place'])
                ->where('status', 'aktif')
                ->whereDoesntHave('assignment')
                ->latest('selected_at')
                ->get(),
            'lecturers' => Lecturer::with('user')->whereHas('user.roles', fn ($q) => $q->where('name', 'pembimbing_dalam'))->orderBy('nidn_nip')->get(),
            'fieldSupervisors' => FieldSupervisor::with('user')->whereHas('user.roles', fn ($q) => $q->where('name', 'pembimbing_lapangan'))->orderBy('institution_name')->get(),
        ];
    }
}
