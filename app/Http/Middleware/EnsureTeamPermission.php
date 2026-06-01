<?php

namespace App\Http\Middleware;

use App\Services\TeamAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeamPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'Acesso nao autorizado.');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $access = app(TeamAccessService::class);
        if (! $access->can($user, $permission)) {
            abort(403, 'Acesso nao autorizado.');
        }

        return $next($request);
    }
}
