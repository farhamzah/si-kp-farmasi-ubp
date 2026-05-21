<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Support\RoleDashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleSelectionController extends Controller
{
    public function index(Request $request): View
    {
        return view('roles.select', [
            'roles' => $request->user()->activeRoles(),
        ]);
    }

    public function store(Request $request, Role $role): RedirectResponse
    {
        $user = $request->user()->load('roles');

        if (! $user->hasRole($role->name)) {
            abort(403, 'Anda tidak memiliki akses untuk role ini.');
        }

        $request->session()->put('active_role', $role->name);

        return redirect()->route(RoleDashboard::routeFor($role->name));
    }
}
