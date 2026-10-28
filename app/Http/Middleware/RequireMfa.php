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
            if (! $request->routeIs('admin.mfa.enroll', 'admin.mfa.confirm', 'logout')) {
                return redirect()->route('admin.mfa.enroll');
            }
            return $next($request);
        }

        if (! $request->session()->boolean('mfa_confirmed')) {
            if (! $request->routeIs('admin.mfa.challenge', 'admin.mfa.verify', 'logout')) {
                return redirect()->route('admin.mfa.challenge');
            }
        }

        return $next($request);
    }
}
