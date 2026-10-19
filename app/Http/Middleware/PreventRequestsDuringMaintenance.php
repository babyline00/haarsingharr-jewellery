<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventRequestsDuringMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->inMaintenance()) {
            $allowedIps = explode(',', env('MAINTENANCE_ALLOWED_IPS', ''));
            if (in_array($request->ip(), array_filter(array_map('trim', $allowedIps)), true)) {
                return $next($request);
            }
            return response()->view('errors.503', [], 503);
        }
        return $next($request);
    }

    private function inMaintenance(): bool
    {
        return file_exists(storage_path('framework/down'));
    }
}
