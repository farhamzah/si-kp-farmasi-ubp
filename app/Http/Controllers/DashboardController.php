<?php

namespace App\Http\Controllers;

use App\Models\KpPeriod;
use App\Models\KpPlace;
use App\Models\KpPlaceQuota;
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
}
