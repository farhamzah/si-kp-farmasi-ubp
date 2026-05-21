<?php

namespace App\Http\Controllers;

use App\Models\KpPeriod;
use App\Models\KpAssignment;
use App\Models\KpLogbook;
use App\Models\KpFinalReport;
use App\Models\KpPlace;
use App\Models\KpPlaceQuota;
use App\Models\KpPlaceSelection;
use App\Models\KpRegistration;
use App\Models\KpWaitingList;
use App\Models\User;
use App\Models\UserImportBatch;
use App\Support\RoleDashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $activeRole = $request->session()->get('active_role');

        return redirect()->route(RoleDashboard::routeFor($activeRole));
    }

    public function show(Request $request, string $role): View
    {
        return view('dashboard.show', [
            'role' => $role,
            'roleData' => RoleDashboard::dataFor($role),
            'features' => RoleDashboard::dataFor($role)['features'],
            'adminStats' => $role === 'admin' ? $this->adminStats() : null,
            'kpStats' => in_array($role, ['admin', 'koordinator_kp'], true) ? $this->kpStats() : null,
            'registrationStats' => in_array($role, ['admin', 'koordinator_kp'], true) ? $this->registrationStats() : null,
            'selectionStats' => in_array($role, ['admin', 'koordinator_kp'], true) ? $this->selectionStats() : null,
            'assignmentStats' => $this->assignmentStats($role, $request),
            'logbookStats' => $this->logbookStats($role, $request),
            'finalReportStats' => $this->finalReportStats($role, $request),
            'studentRegistration' => $role === 'mahasiswa' ? $request->user()->student?->kpRegistrations()->with(['documents', 'activePlaceSelection.place', 'waitingList'])->latest()->first() : null,
        ]);
    }

    private function adminStats(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'inactive_users' => User::where('status', 'inactive')->count(),
            'incomplete_profiles' => User::where('profile_completed', false)->count(),
            'last_import' => UserImportBatch::latest()->first(),
        ];
    }

    private function kpStats(): array
    {
        return [
            'total_periods' => KpPeriod::count(),
            'open_periods' => KpPeriod::where('status', 'dibuka')->count(),
            'active_places' => KpPlace::where('status', 'aktif')->count(),
            'total_quota' => KpPlaceQuota::sum('quota'),
            'open_quotas' => KpPlaceQuota::where('is_open', true)->count(),
        ];
    }

    private function registrationStats(): array
    {
        return [
            'total' => KpRegistration::count(),
            'pending' => KpRegistration::where('status', 'menunggu_verifikasi')->count(),
            'revision' => KpRegistration::where('status', 'revisi')->count(),
            'verified' => KpRegistration::where('status', 'terverifikasi')->count(),
            'rejected' => KpRegistration::where('status', 'ditolak')->count(),
        ];
    }

    private function selectionStats(): array
    {
        $totalQuota = KpPlaceQuota::sum('quota');
        $selected = KpPlaceSelection::where('status', 'aktif')->count();

        return [
            'selected' => $selected,
            'waiting' => KpWaitingList::where('status', 'menunggu')->count(),
            'remaining_quota' => max(0, $totalQuota - $selected),
            'full_places' => KpPlaceQuota::get()->filter->isFull()->count(),
        ];
    }

    private function assignmentStats(string $role, Request $request): ?array
    {
        if (in_array($role, ['admin', 'koordinator_kp'], true)) {
            return [
                'total' => KpAssignment::count(),
                'waiting' => KpAssignment::where('status', 'menunggu_pembimbing')->count(),
                'active' => KpAssignment::whereIn('status', ['aktif', 'berjalan'])->count(),
                'cancelled' => KpAssignment::where('status', 'dibatalkan')->count(),
                'unassigned_selection' => KpPlaceSelection::where('status', 'aktif')->whereDoesntHave('assignment')->count(),
            ];
        }

        if ($role === 'pembimbing_dalam') {
            return [
                'total' => KpAssignment::where('internal_supervisor_id', $request->user()->lecturer?->id)->count(),
                'active' => KpAssignment::where('internal_supervisor_id', $request->user()->lecturer?->id)->whereIn('status', ['aktif', 'berjalan'])->count(),
            ];
        }

        if ($role === 'pembimbing_lapangan') {
            return [
                'total' => KpAssignment::where('field_supervisor_id', $request->user()->fieldSupervisor?->id)->count(),
                'active' => KpAssignment::where('field_supervisor_id', $request->user()->fieldSupervisor?->id)->whereIn('status', ['aktif', 'berjalan'])->count(),
            ];
        }

        return null;
    }

    private function logbookStats(string $role, Request $request): ?array
    {
        if (in_array($role, ['admin', 'koordinator_kp'], true)) {
            return [
                'total' => KpLogbook::count(),
                'menunggu_validasi' => KpLogbook::where('status', 'menunggu_validasi')->count(),
                'disetujui' => KpLogbook::where('status', 'disetujui')->count(),
                'revisi' => KpLogbook::where('status', 'revisi')->count(),
                'ditolak' => KpLogbook::where('status', 'ditolak')->count(),
            ];
        }

        if ($role === 'mahasiswa') {
            $assignment = $request->user()->student?->assignments()->whereIn('status', ['aktif', 'berjalan'])->latest()->first();

            return $assignment ? [
                'total' => $assignment->logbooks()->count(),
                'menunggu_validasi' => $assignment->logbooks()->where('status', 'menunggu_validasi')->count(),
                'disetujui' => $assignment->logbooks()->where('status', 'disetujui')->count(),
                'revisi' => $assignment->logbooks()->where('status', 'revisi')->count(),
            ] : ['total' => 0, 'menunggu_validasi' => 0, 'disetujui' => 0, 'revisi' => 0];
        }

        if ($role === 'pembimbing_lapangan') {
            $fieldSupervisorId = $request->user()->fieldSupervisor?->id;

            return [
                'total' => KpLogbook::whereHas('assignment', fn ($q) => $q->where('field_supervisor_id', $fieldSupervisorId))->count(),
                'menunggu_validasi' => KpLogbook::where('status', 'menunggu_validasi')->whereHas('assignment', fn ($q) => $q->where('field_supervisor_id', $fieldSupervisorId))->count(),
            ];
        }

        if ($role === 'pembimbing_dalam') {
            $lecturerId = $request->user()->lecturer?->id;

            return [
                'total' => KpLogbook::whereHas('assignment', fn ($q) => $q->where('internal_supervisor_id', $lecturerId))->count(),
                'komentar' => KpLogbook::whereHas('comments', fn ($q) => $q->where('user_id', $request->user()->id))->count(),
            ];
        }

        return null;
    }

    private function finalReportStats(string $role, Request $request): ?array
    {
        if (in_array($role, ['admin', 'koordinator_kp'], true)) {
            return [
                'total' => KpFinalReport::count(),
                'menunggu_review' => KpFinalReport::where('status', 'menunggu_review')->count(),
                'revisi' => KpFinalReport::where('status', 'revisi')->count(),
                'disetujui' => KpFinalReport::where('status', 'disetujui')->count(),
            ];
        }

        if ($role === 'pembimbing_dalam') {
            $lecturerId = $request->user()->lecturer?->id;

            return [
                'menunggu_review' => KpFinalReport::where('status', 'menunggu_review')->whereHas('assignment', fn ($q) => $q->where('internal_supervisor_id', $lecturerId))->count(),
                'revisi' => KpFinalReport::where('status', 'revisi')->whereHas('assignment', fn ($q) => $q->where('internal_supervisor_id', $lecturerId))->count(),
                'disetujui' => KpFinalReport::where('status', 'disetujui')->whereHas('assignment', fn ($q) => $q->where('internal_supervisor_id', $lecturerId))->count(),
            ];
        }

        if ($role === 'mahasiswa') {
            $assignment = $request->user()->student?->assignments()->whereIn('status', ['aktif', 'berjalan'])->latest()->first();
            $report = $assignment?->finalReport;

            return [
                'status_laporan' => $report?->statusLabel() ?? 'Belum upload',
                'versi' => $report?->current_version ?? 0,
            ];
        }

        return null;
    }
}
