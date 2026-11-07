<?php

use App\Http\Middleware\DetectLocale;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Only the non-sensitive light/dark preference is readable by JS.
        // Authentication and session cookies remain encrypted by Laravel.
        $middleware->encryptCookies(except: ['mm-theme']);
        // All authenticated roles return to a dashboard they can actually access.
        // Laravel's default guest redirect prefers /dashboard, which is admin-only.
        $middleware->redirectUsersTo(fn (Request $request) => match (true) {
            $request->user()?->isAdmin() => route('admin.home'),
            $request->user()?->isApprovedVendor() => route('vendor.home'),
            default => route('account.home'),
        });
        $middleware->alias(['role' => RequireRole::class]);
        $middleware->appendToGroup('web', DetectLocale::class);
        $middleware->appendToGroup('web', SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
