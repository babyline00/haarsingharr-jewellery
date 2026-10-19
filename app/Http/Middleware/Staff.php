<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Staff
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isStaff()) {
            abort(403, 'You are not authorised to access the back office.');
        }
        if (! $request->user()->isActive()) {
            abort(403, 'Your staff account is suspended. Contact the Super Admin.');
        }
        return $next($request);
    }
}
