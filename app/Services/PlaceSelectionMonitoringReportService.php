<?php

namespace App\Services;

use App\Models\KpPeriod;
use App\Models\KpPlaceQuota;
use App\Models\KpPlaceSelection;
use App\Models\KpRegistration;
use App\Models\KpWaitingList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PlaceSelectionMonitoringReportService
{
    public function query(Request $request): Builder
    {
        return KpPlaceSelection::query()
            ->with(['period', 'student.user', 'place', 'quota', 'selectedBy'])
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
            ->latest('selected_at');
    }

    public function rows(Request $request): Collection
    {
        return $this->query($request)
            ->get()
            ->values()
            ->map(fn (KpPlaceSelection $selection, int $index) => [
                'No' => $index + 1,
                'Mahasiswa' => $selection->student?->user?->name ?? '-',
                'NIM' => $selection->student?->nim ?: '-',
                'Email' => $selection->student?->user?->email ?? '-',
                'Periode' => $selection->period?->name ?? '-',
                'Tempat KP' => $selection->place?->name ?? '-',
                'Tipe Tempat' => $selection->place?->typeLabel() ?? '-',
                'Kuota' => $selection->quota?->quota ?? '-',
                'Waktu Pilih' => $selection->selected_at?->format('d M Y H:i') ?? '-',
                'Status' => $selection->statusLabel(),
                'Dipilih Oleh' => $selection->selectedBy?->name ?? 'Mahasiswa',
                'Catatan' => $selection->note ?: '-',
            ]);
    }

    public function stats(): array
    {
        $verified = KpRegistration::where('status', 'terverifikasi')->count();
        $selected = KpPlaceSelection::where('status', 'aktif')->count();
        $quota = KpPlaceQuota::sum('quota');
        $filled = KpPlaceSelection::where('status', 'aktif')->count();

        return [
            'verified' => $verified,
            'selected' => $selected,
            'not_selected' => max(0, $verified - $selected),
            'waiting' => KpWaitingList::where('status', 'menunggu')->count(),
            'total_quota' => $quota,
            'remaining_quota' => max(0, $quota - $filled),
            'full_places' => KpPlaceQuota::get()->filter->isFull()->count(),
        ];
    }

    public function periods(): Collection
    {
        return KpPeriod::latest()->get();
    }

    public function filterSummary(Request $request): array
    {
        $period = $request->filled('period') ? KpPeriod::find($request->period)?->name : null;

        return [
            'Periode' => $period ?: 'Semua periode',
            'Status' => $request->filled('status') ? ucfirst((string) $request->status) : 'Semua status',
            'Pencarian' => $request->filled('q') ? (string) $request->q : '-',
            'Dicetak pada' => now()->format('d M Y H:i'),
        ];
    }
}

