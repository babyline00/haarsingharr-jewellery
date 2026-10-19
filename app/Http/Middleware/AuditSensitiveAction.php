<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditSensitiveAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $auditService = app(\App\Services\AuditService::class);
            $auditService->record(
                action: 'sensitive_action',
                target: $request->path(),
                previous: null,
                after: array_filter($request->except(['password', 'password_confirmation', 'cvv', 'card_number', 'pin', 'token'])),
                module: 'sensitive',
            );
        }
        return $response;
    }
}
