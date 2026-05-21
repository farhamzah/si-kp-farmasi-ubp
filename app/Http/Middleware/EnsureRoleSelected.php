<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user()?->loadMissing('roles');

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->roles->isEmpty()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun belum memiliki role. Silakan hubungi Admin.']);
        }

        $activeRole = $request->session()->get('active_role');

        if (! $activeRole || ! $user->hasRole($activeRole)) {
            $request->session()->forget('active_role');

            if ($user->roles->count() === 1) {
                $request->session()->put('active_role', $user->roles->first()->name);

                return $next($request);
            }

            return redirect()->route('role.select');
        }

        return $next($request);
    }
}
