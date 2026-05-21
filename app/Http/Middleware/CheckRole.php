<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $activeRole = $request->session()->get('active_role');
        $allowedRoles = collect($roles)
            ->flatMap(fn ($role) => explode(',', $role))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();

        if (! in_array($activeRole, $allowedRoles, true) || ! $request->user()?->hasRole($activeRole)) {
            abort(403, 'Anda tidak memiliki akses ke dashboard ini.');
        }

        return $next($request);
    }
}
