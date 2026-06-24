<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\CancelSelectionRequest;
use App\Http\Requests\Management\MoveSelectionRequest;
use App\Http\Requests\Management\StoreManualPlaceSelectionRequest;
use App\Models\KpPeriod;
use App\Models\KpPlaceQuota;
use App\Models\KpPlaceSelection;
use App\Models\KpRegistration;
use App\Services\KpPlaceSelectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlaceSelectionMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $selections = KpPlaceSelection::query()
            ->with(['period', 'student.user', 'place', 'quota'])
            ->when($request->filled('period'), fn ($query) => $query->where('kp_period_id', $request->period))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = $request->q;
                $query->where(function ($inner) use ($keyword) {
                    $inner->whereHas('student', fn ($student) => $student->where('nim', 'like', "%{$keyword}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%")))
                        ->orWhereHas('place', fn ($place) => $place->where('name', 'like', "%{$keyword}%"));
                });
            })
            ->latest('selected_at')
            ->paginate(10)
            ->withQueryString();

        return view('management.place-selections.index', [
            'selections' => $selections,
            'periods' => KpPeriod::latest()->get(),
            'filters' => $request->only(['period', 'status', 'q']),
            'stats' => $this->stats(),
        ]);
    }

    public function show(KpPlaceSelection $selection): View
    {
        return view('management.place-selections.show', [
            'selection' => $selection->load(['period', 'registration', 'student.user', 'place', 'quota', 'selectedBy', 'cancelledBy', 'assignment']),
        ]);
    }

    public function manual(): View
    {
        return view('management.place-selections.manual', [
            'registrations' => $this->eligibleManualRegistrations(),
            'quotas' => $this->availableManualQuotas(),
        ]);
    }

    public function storeManual(StoreManualPlaceSelectionRequest $request, KpPlaceSelectionService $service): RedirectResponse
    {
        $selection = $service->selectPlaceManually(
            $request->user(),
            KpRegistration::findOrFail($request->integer('kp_registration_id')),
            KpPlaceQuota::findOrFail($request->integer('kp_place_quota_id')),
            $request->reason
        );

        return redirect()->route('management.place-selections.show', $selection)->with('status', 'Tempat KP mahasiswa berhasil dipilihkan secara manual.');
    }

    public function cancel(CancelSelectionRequest $request, KpPlaceSelection $selection, KpPlaceSelectionService $service): RedirectResponse
    {
        $service->cancelSelection($request->user(), $selection, $request->reason);

        return back()->with('status', 'Pilihan tempat KP berhasil dibatalkan.');
    }

    public function move(KpPlaceSelection $selection): View
    {
        return view('management.place-selections.move', [
            'selection' => $selection->load(['period', 'student.user', 'place']),
            'quotas' => KpPlaceQuota::with('place')
                ->where('kp_period_id', $selection->kp_period_id)
                ->where('is_open', true)
                ->get(),
        ]);
    }

    public function moveStore(MoveSelectionRequest $request, KpPlaceSelection $selection, KpPlaceSelectionService $service): RedirectResponse
    {
        $newSelection = $service->moveSelection($request->user(), $selection, KpPlaceQuota::findOrFail($request->kp_place_quota_id), $request->reason);

        return redirect()->route('management.place-selections.show', $newSelection)->with('status', 'Pilihan tempat KP berhasil dipindahkan.');
    }

    private function stats(): array
    {
        $verified = \App\Models\KpRegistration::where('status', 'terverifikasi')->count();
        $selected = KpPlaceSelection::where('status', 'aktif')->count();
        $quota = KpPlaceQuota::sum('quota');
        $filled = KpPlaceSelection::where('status', 'aktif')->count();

        return [
            'verified' => $verified,
            'selected' => $selected,
            'not_selected' => max(0, $verified - $selected),
            'waiting' => \App\Models\KpWaitingList::where('status', 'menunggu')->count(),
            'total_quota' => $quota,
            'remaining_quota' => max(0, $quota - $filled),
            'full_places' => KpPlaceQuota::get()->filter->isFull()->count(),
        ];
    }

    private function eligibleManualRegistrations()
    {
        return KpRegistration::query()
            ->with(['period.documentRequirements', 'documents', 'student.user'])
            ->where('status', 'terverifikasi')
            ->whereDoesntHave('activePlaceSelection')
            ->whereDoesntHave('assignment', fn ($query) => $query->where('status', '!=', 'dibatalkan'))
            ->latest()
            ->get()
            ->filter(fn (KpRegistration $registration): bool => $registration->isEligibleForPlaceSelection())
            ->values();
    }

    private function availableManualQuotas()
    {
        return KpPlaceQuota::query()
            ->with(['period', 'place'])
            ->where('is_open', true)
            ->latest()
            ->get()
            ->filter(fn (KpPlaceQuota $quota): bool => $quota->remainingQuota() > 0)
            ->values();
    }
}
