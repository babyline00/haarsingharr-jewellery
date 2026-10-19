<?php

namespace App\Http\Middleware;

use App\Models\ActiveSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RequireMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }
        // Only staff with privileged roles need MFA.
        if (! $user->requiresMfa()) {
            return $next($request);
        }
        if (! $user->hasMfaEnabled()) {
            // Force enrollment after first login.
            if (! $request->routeIs('admin.mfa.enroll') && ! $request->routeIs('admin.mfa.confirm') && ! $request->routeIs('logout')) {
                return redirect()->route('admin.mfa.enroll');
            }
        }
        // MFA confirmed → carry on.
        return $next($request);
    }
}
