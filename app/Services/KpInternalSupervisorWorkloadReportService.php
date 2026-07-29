<?php

namespace App\Services;

use App\Models\KpAssignment;
use App\Models\KpPeriod;
use App\Models\KpPlace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KpInternalSupervisorWorkloadReportService
{
    public function query(Request $request): Builder
    {
        return KpAssignment::query()
            ->with(['period', 'place', 'internalSupervisor.user'])
            ->whereNotNull('internal_supervisor_id')
            ->when($request->filled('period'), fn ($query) => $query->where('kp_period_id', $request->period))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status), fn ($query) => $query->where('status', '!=', 'dibatalkan'))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $keyword = $request->q;
                $query->whereHas('internalSupervisor', fn ($lecturer) => $lecturer
                    ->where('nidn_nip', 'like', "%{$keyword}%")
                    ->orWhere('employee_number', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%")));
            });
    }

    public function rows(Request $request, bool $includeTotals = true): Collection
    {
        $masterData = app(KpMasterDataReadService::class);
        $groups = $this->query($request)
            ->get()
            ->groupBy('internal_supervisor_id')
            ->map(function (Collection $assignments) use ($masterData): array {
                $lecturer = $assignments->first()?->internalSupervisor;
                $display = $lecturer ? $masterData->getLecturerDisplayData($lecturer) : null;
                $counts = [
                    'rs' => 0,
                    'apotek' => 0,
                    'industri' => 0,
                    'lainnya' => 0,
                ];

                foreach ($assignments as $assignment) {
                    $counts[$this->placeCategory($assignment->place)]++;
                }

                return [
                    'lecturer_name' => $display?->name ?? $lecturer?->user?->name ?? '-',
                    'rs' => $counts['rs'],
                    'apotek' => $counts['apotek'],
                    'industri' => $counts['industri'],
                    'lainnya' => $counts['lainnya'],
                    'total' => array_sum($counts),
                ];
            })
            ->sortBy('lecturer_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $rows = $groups->map(fn (array $row, int $index): array => [
            'No' => $index + 1,
            'Nama Dosen Pembimbing' => $row['lecturer_name'],
            'RS' => $row['rs'],
            'Apotek' => $row['apotek'],
            'Industri' => $row['industri'],
            'Lainnya' => $row['lainnya'],
            'Total' => $row['total'],
        ]);

        if ($includeTotals && $rows->isNotEmpty()) {
            $rows->push([
                'No' => '',
                'Nama Dosen Pembimbing' => 'TOTAL',
                'RS' => $groups->sum('rs'),
                'Apotek' => $groups->sum('apotek'),
                'Industri' => $groups->sum('industri'),
                'Lainnya' => $groups->sum('lainnya'),
                'Total' => $groups->sum('total'),
            ]);
        }

        return $rows;
    }

    public function filterSummary(Request $request): array
    {
        return [
            'Nama/NIDN pembimbing' => $request->filled('q') ? $request->q : 'Semua',
            'Periode' => $this->periodLabel($request),
            'Status penempatan' => $request->filled('status') ? ($this->statusOptions()[$request->status] ?? $request->status) : 'Semua aktif/non-batal',
            'Dicetak pada' => now()->format('d/m/Y H:i'),
        ];
    }

    public function periods(): Collection
    {
        return KpPeriod::latest()->get();
    }

    public function statusOptions(): array
    {
        return [
            'menunggu_pembimbing' => 'Menunggu Pembimbing',
            'aktif' => 'Aktif',
            'berjalan' => 'Berjalan',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
        ];
    }

    private function placeCategory(?KpPlace $place): string
    {
        $type = Str::of((string) $place?->type)->lower()->replace(['-', ' '], '_')->value();
        $name = Str::of((string) $place?->name)->lower()->value();
        $text = trim($type.' '.$name);

        if (str_contains($text, 'rumah_sakit') || preg_match('/\brs\.?\b|rsud/', $text)) {
            return 'rs';
        }

        if (str_contains($text, 'apotek') || str_contains($text, 'apotik')) {
            return 'apotek';
        }

        if (str_contains($text, 'industri')) {
            return 'industri';
        }

        return 'lainnya';
    }

    private function periodLabel(Request $request): string
    {
        if (! $request->filled('period')) {
            return 'Semua';
        }

        return KpPeriod::find($request->period)?->name ?? 'Periode tidak ditemukan';
    }
}
