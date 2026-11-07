<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        abort_unless($request->user()?->role === $role, 403);

        if ($role === 'vendor') {
            abort_unless($request->user()->vendor?->status === 'approved', 403, 'Vendor approval is required.');
        }

        return $next($request);
    }
}
