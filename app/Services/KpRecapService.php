<?php

namespace App\Services;

use App\Models\KpAssignment;
use App\Models\KpExam;
use App\Models\KpLogbook;
use App\Models\KpPeriod;
use App\Models\KpPlace;
use App\Models\KpRegistration;
use App\Support\KpScoreCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class KpRecapService
{
    public function __construct(private readonly KpScoreCalculator $scoreCalculator)
    {
    }

    public function summary(): array
    {
        return [
            'periods' => KpPeriod::count(),
            'registrations' => KpRegistration::count(),
            'verified_registrations' => KpRegistration::where('status', 'terverifikasi')->count(),
            'assignments' => KpAssignment::count(),
            'exams' => KpExam::count(),
            'published_scores' => KpAssignment::whereHas('finalScore', fn ($q) => $q->where('status', 'published'))->count(),
        ];
    }

    public function studentRows(Request $request): Collection
    {
        return $this->assignmentQuery($request)
            ->with(['registration.documents', 'student.user', 'period', 'place', 'internalSupervisor.user', 'fieldSupervisor.user', 'finalReport', 'exam', 'finalScore'])
            ->get()
            ->map(fn (KpAssignment $assignment) => [
                'Periode' => $assignment->period->name,
                'NIM' => $assignment->student->nim,
                'Nama Mahasiswa' => $assignment->student->user->name,
                'Email' => $assignment->student->user->email,
                'Status Profil' => $assignment->student->user->profile_completed ? 'Lengkap' : 'Belum lengkap',
                'Status Pendaftaran' => $assignment->registration?->statusLabel() ?? '-',
                'Status Dokumen' => $assignment->registration?->allRequiredDocumentsApproved() ? 'Lengkap disetujui' : 'Belum lengkap',
                'Tempat KP' => $assignment->place->name,
                'Pembimbing Dalam' => $this->lecturerName($assignment->internalSupervisor),
                'Pembimbing Lapangan' => $assignment->fieldSupervisor ? field_supervisor_display_name($assignment->fieldSupervisor) : '-',
                'Status Assignment' => $assignment->statusLabel(),
                'Status Laporan' => $assignment->finalReport?->statusLabel() ?? '-',
                'Status Sidang' => $assignment->exam?->statusLabel() ?? '-',
                'Nilai Akhir' => $assignment->finalScore?->final_score ?? '-',
                'Grade' => $assignment->finalScore?->final_grade ?? '-',
            ]);
    }

    public function placementRows(Request $request): Collection
    {
        return $this->assignmentQuery($request)
            ->with(['student.user', 'period', 'place.quotas', 'internalSupervisor.user', 'fieldSupervisor.user'])
            ->get()
            ->map(fn (KpAssignment $assignment) => [
                'Periode' => $assignment->period->name,
                'Mahasiswa' => $assignment->student->user->name,
                'NIM' => $assignment->student->nim,
                'Tempat KP' => $assignment->place->name,
                'Tipe Tempat' => $assignment->place->typeLabel(),
                'Kuota' => $assignment->place->quotas->firstWhere('kp_period_id', $assignment->kp_period_id)?->quota ?? '-',
                'Pembimbing Dalam' => $this->lecturerName($assignment->internalSupervisor),
                'Pembimbing Lapangan' => $assignment->fieldSupervisor ? field_supervisor_display_name($assignment->fieldSupervisor) : '-',
                'Status Penempatan' => $assignment->statusLabel(),
                'Tanggal Assignment' => $assignment->assigned_at?->format('d/m/Y H:i') ?? '-',
            ]);
    }

    public function logbookRows(Request $request): Collection
    {
        return $this->assignmentQuery($request)
            ->with(['student.user', 'period', 'place', 'logbooks'])
            ->get()
            ->map(function (KpAssignment $assignment) {
                $total = $assignment->logbooks->count();
                $approved = $assignment->logbooks->where('status', 'disetujui')->count();

                return [
                    'Periode' => $assignment->period->name,
                    'Mahasiswa' => $assignment->student->user->name,
                    'NIM' => $assignment->student->nim,
                    'Tempat KP' => $assignment->place->name,
                    'Total Logbook' => $total,
                    'Draft' => $assignment->logbooks->where('status', 'draft')->count(),
                    'Menunggu Validasi' => $assignment->logbooks->where('status', 'menunggu_validasi')->count(),
                    'Disetujui' => $approved,
                    'Revisi' => $assignment->logbooks->where('status', 'revisi')->count(),
                    'Ditolak' => $assignment->logbooks->where('status', 'ditolak')->count(),
                    'Persentase Disetujui' => $total > 0 ? round(($approved / $total) * 100, 2).'%' : '0%',
                ];
            });
    }

    public function examRows(Request $request): Collection
    {
        return KpExam::query()
            ->with(['assignment.student.user', 'assignment.period', 'assignment.place', 'supervisor.user', 'examiner.user', 'examiners.user'])
            ->when($request->filled('period'), fn ($q) => $q->whereHas('assignment', fn ($a) => $a->where('kp_period_id', $request->period)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->get()
            ->map(fn (KpExam $exam) => [
                'Periode' => $exam->assignment->period->name,
                'Mahasiswa' => $exam->assignment->student->user->name,
                'NIM' => $exam->assignment->student->nim,
                'Tempat KP' => $exam->assignment->place->name,
                'Pembimbing Dalam' => $this->lecturerName($exam->supervisor),
                'Penguji' => $exam->examinerNamesLabel(),
                'Tanggal Sidang' => $exam->exam_date?->format('d/m/Y') ?? '-',
                'Jam' => substr((string) $exam->start_time, 0, 5).' - '.substr((string) $exam->end_time, 0, 5),
                'Mode' => $exam->modeLabel(),
                'Status Sidang' => $exam->statusLabel(),
            ]);
    }

    public function scoreRows(Request $request): Collection
    {
        return $this->assignmentQuery($request)
            ->with(['student.user', 'period.assessmentComponents', 'place', 'scores.component', 'logbooks', 'finalScore'])
            ->get()
            ->map(function (KpAssignment $assignment) {
                $breakdown = $this->scoreCalculator->breakdown($assignment);

                return [
                    'Periode' => $assignment->period->name,
                    'Mahasiswa' => $assignment->student->user->name,
                    'NIM' => $assignment->student->nim,
                    'Tempat KP' => $assignment->place->name,
                    'Nilai Kehadiran' => $breakdown['sections']['kehadiran']['score'],
                    'Kontribusi Kehadiran' => $breakdown['sections']['kehadiran']['contribution'],
                    'Nilai Pembimbing Dalam' => $breakdown['sections']['pembimbing_dalam']['score'],
                    'Kontribusi Pembimbing Dalam' => $breakdown['sections']['pembimbing_dalam']['contribution'],
                    'Nilai Pembimbing Lapangan' => $breakdown['sections']['pembimbing_lapangan']['score'],
                    'Kontribusi Pembimbing Lapangan' => $breakdown['sections']['pembimbing_lapangan']['contribution'],
                    'Nilai Penguji' => $breakdown['sections']['penguji']['score'],
                    'Kontribusi Penguji' => $breakdown['sections']['penguji']['contribution'],
                    'Nilai Akhir' => $assignment->finalScore?->final_score ?? $breakdown['final_score'],
                    'Grade' => $assignment->finalScore?->final_grade ?? '-',
                    'Status Final Score' => $assignment->finalScore?->statusLabel() ?? '-',
                    'Published At' => $assignment->finalScore?->published_at?->format('d/m/Y H:i') ?? '-',
                ];
            });
    }

    public function supervisorRows(Request $request): Collection
    {
        $assignments = KpAssignment::query()
            ->with(['student.user', 'period', 'place', 'internalSupervisor.user', 'fieldSupervisor.user'])
            ->when($request->filled('period'), fn ($q) => $q->where('kp_period_id', $request->period))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $keyword = (string) $request->q;

                $query->where(function ($query) use ($keyword): void {
                    $query
                        ->whereHas('student', fn ($student) => $student
                            ->where('nim', 'like', "%{$keyword}%")
                            ->orWhereHas('user', fn ($user) => $user
                                ->where('name', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%")))
                        ->orWhereHas('place', fn ($place) => $place
                            ->where('name', 'like', "%{$keyword}%")
                            ->orWhere('type', 'like', "%{$keyword}%"))
                        ->orWhereHas('internalSupervisor', fn ($lecturer) => $lecturer
                            ->where('nidn_nip', 'like', "%{$keyword}%")
                            ->orWhere('employee_number', 'like', "%{$keyword}%")
                            ->orWhere('study_program', 'like', "%{$keyword}%")
                            ->orWhere('department', 'like', "%{$keyword}%")
                            ->orWhereHas('user', fn ($user) => $user
                                ->where('name', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%")))
                        ->orWhereHas('fieldSupervisor', fn ($fieldSupervisor) => $fieldSupervisor
                            ->where('institution_name', 'like', "%{$keyword}%")
                            ->orWhere('position', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%")
                            ->orWhereHas('user', fn ($user) => $user
                                ->where('name', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%")));
                });
            })
            ->get();

        $rows = collect()
            ->merge($this->supervisorGroupRows($assignments->whereNotNull('internal_supervisor_id'), 'internal'))
            ->merge($this->supervisorGroupRows($assignments->whereNotNull('field_supervisor_id'), 'field'))
            ->sortBy([['Jenis Pembimbing', 'asc'], ['Nama Pembimbing', 'asc']])
            ->values();

        if ($rows->isEmpty()) {
            return $rows;
        }

        return $rows->push([
            'Jenis Pembimbing' => 'TOTAL PERAN PEMBIMBING',
            'Nama Pembimbing' => '-',
            'Identitas/Kontak' => '-',
            'Instansi/Unit' => '-',
            'Mahasiswa Aktif' => $rows->sum('Mahasiswa Aktif'),
            'RS' => $rows->sum('RS'),
            'Apotek' => $rows->sum('Apotek'),
            'Industri' => $rows->sum('Industri'),
            'Lainnya' => $rows->sum('Lainnya'),
            'Tempat Aktif' => $rows->sum('Tempat Aktif'),
            'Menunggu Pembimbing' => $rows->sum('Menunggu Pembimbing'),
            'Aktif/Berjalan' => $rows->sum('Aktif/Berjalan'),
            'Selesai' => $rows->sum('Selesai'),
            'Dibatalkan' => $rows->sum('Dibatalkan'),
            'Total Baris' => $rows->sum('Total Baris'),
        ]);
    }

    public function rows(string $type, Request $request): Collection
    {
        return match ($type) {
            'students' => $this->studentRows($request),
            'placements' => $this->placementRows($request),
            'logbooks' => $this->logbookRows($request),
            'exams' => $this->examRows($request),
            'scores' => $this->scoreRows($request),
            'supervisors' => $this->supervisorRows($request),
            default => collect(),
        };
    }

    private function assignmentQuery(Request $request): Builder
    {
        return KpAssignment::query()
            ->when($request->filled('period'), fn ($q) => $q->where('kp_period_id', $request->period))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('student', fn ($s) => $s->where('nim', 'like', "%{$request->q}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$request->q}%"))))
            ->latest();
    }

    private function lecturerName(mixed $lecturer): string
    {
        return $lecturer ? lecturer_display_name($lecturer) : '-';
    }

    private function supervisorGroupRows(Collection $assignments, string $type): Collection
    {
        $supervisorKey = $type === 'internal' ? 'internal_supervisor_id' : 'field_supervisor_id';

        return $assignments
            ->groupBy($supervisorKey)
            ->map(function (Collection $items) use ($type): array {
                /** @var KpAssignment $sample */
                $sample = $items->first();
                $supervisor = $type === 'internal' ? $sample->internalSupervisor : $sample->fieldSupervisor;
                $activeItems = $items->reject(fn (KpAssignment $assignment) => $assignment->status === 'dibatalkan');
                $placeCounts = $activeItems->countBy(fn (KpAssignment $assignment) => $this->placeCategory($assignment->place));

                return [
                    'Jenis Pembimbing' => $type === 'internal' ? 'Pembimbing Dalam' : 'Pembimbing Lapangan',
                    'Nama Pembimbing' => $type === 'internal' ? $this->lecturerName($supervisor) : field_supervisor_display_name($supervisor),
                    'Identitas/Kontak' => $this->supervisorIdentifier($supervisor, $type),
                    'Instansi/Unit' => $this->supervisorUnit($supervisor, $type),
                    'Mahasiswa Aktif' => $activeItems->pluck('student_id')->unique()->count(),
                    'RS' => (int) ($placeCounts['rs'] ?? 0),
                    'Apotek' => (int) ($placeCounts['apotek'] ?? 0),
                    'Industri' => (int) ($placeCounts['industri'] ?? 0),
                    'Lainnya' => (int) ($placeCounts['lainnya'] ?? 0),
                    'Tempat Aktif' => $activeItems->pluck('kp_place_id')->unique()->count(),
                    'Menunggu Pembimbing' => $items->where('status', 'menunggu_pembimbing')->count(),
                    'Aktif/Berjalan' => $items->whereIn('status', ['aktif', 'berjalan'])->count(),
                    'Selesai' => $items->where('status', 'selesai')->count(),
                    'Dibatalkan' => $items->where('status', 'dibatalkan')->count(),
                    'Total Baris' => $items->count(),
                ];
            })
            ->values();
    }

    private function supervisorIdentifier(mixed $supervisor, string $type): string
    {
        if (! $supervisor) {
            return '-';
        }

        if ($type === 'internal') {
            return $supervisor->nidn_nip ?: ($supervisor->employee_number ?: ($supervisor->user?->email ?? '-'));
        }

        return $supervisor->phone ?: ($supervisor->user?->email ?? '-');
    }

    private function supervisorUnit(mixed $supervisor, string $type): string
    {
        if (! $supervisor) {
            return '-';
        }

        if ($type === 'internal') {
            return collect([$supervisor->study_program, $supervisor->department])->filter()->implode(' / ') ?: '-';
        }

        return collect([$supervisor->institution_name, $supervisor->position])->filter()->implode(' / ') ?: '-';
    }

    private function placeCategory(?KpPlace $place): string
    {
        $value = strtolower(trim(($place?->type ?? '').' '.($place?->name ?? '')));

        return match (true) {
            str_contains($value, 'rumah sakit') || str_contains($value, 'rs') => 'rs',
            str_contains($value, 'apotek') || str_contains($value, 'apotik') => 'apotek',
            str_contains($value, 'industri') || str_contains($value, 'pt ') || str_starts_with($value, 'pt') || str_contains($value, 'cv ') || str_starts_with($value, 'cv') => 'industri',
            default => 'lainnya',
        };
    }
}
