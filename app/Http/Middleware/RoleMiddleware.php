<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $forbiddenResponse = function () use ($request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            abort(403, 'Forbidden');
        };

        if (!$user || !$user->role?->name) {
            return $forbiddenResponse();
        }

        if (!empty($roles) && !in_array($user->role->name, $roles, true)) {
            return $forbiddenResponse();
        }

        return $next($request);
    }
}
