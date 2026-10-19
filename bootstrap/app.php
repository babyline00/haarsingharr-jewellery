<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\TrimStrings as TrimStringsMiddleware;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EncryptCookies as AppEncryptCookies;
use App\Http\Middleware\Staff;
use App\Http\Middleware\RequireMfa;
use App\Http\Middleware\AuditSensitiveAction;
use App\Http\Middleware\RateLimitByRole;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Spatie\Permission\Middleware\PermissionMiddleware as SpatiePermission;
use Spatie\Permission\Middleware\RoleMiddleware as SpatieRole;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware as SpatieRoleOrPermission;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: TrustProxies::class);
        $middleware->trustHosts(at: '*');
        $middleware->preventRequestsDuringMaintenance(PreventRequestsDuringMaintenance::class);
        $middleware->redirectGuestsTo(fn (Request $r) => route('login'));
        $middleware->redirectUsersTo(fn (Request $r) => route('account.dashboard'));
        $middleware->trimStrings(except: [
            'current_password',
            'password',
            'password_confirmation',
        ]);
        $middleware->convertEmptyStringsToNull(except: [
            'description',
            'notes',
            'address',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SecureHeaders::class,
        ]);

        // Aliases for custom middleware
        $middleware->alias([
            'staff'              => Staff::class,
            'mfa'                => RequireMfa::class,
            'audit'              => AuditSensitiveAction::class,
            'role'               => SpatieRole::class,
            'permission'         => SpatiePermission::class,
            'role_or_permission' => SpatieRoleOrPermission::class,
            'throttle.role'      => RateLimitByRole::class,
        ]);

        $middleware->priority([
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SecureHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom exception rendering is handled in app/Providers/AppServiceProvider.php
    })
    ->create();
