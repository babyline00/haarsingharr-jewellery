<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureHeaders
{
    private array $headers = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options'        => 'SAMEORIGIN',
        'X-XSS-Protection'      => '1; mode=block',
        'Referrer-Policy'       => 'strict-origin-when-cross-origin',
        'Permissions-Policy'    => 'geolocation=(), microphone=(), camera=()',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach ($this->headers as $name => $value) {
            $response->headers->set($name, $value, false);
        }

        // HSTS — only meaningful over HTTPS.
        if ($request->secure() || env('APP_FORCE_HTTPS', true)) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        // Content Security Policy — strict baseline.
        // Alpine.js evaluates inline expressions with `new Function()`, which
        // requires 'unsafe-eval'; without it every interactive component on the
        // storefront and admin panel silently fails.
        $csp = "default-src 'self'; ".
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com https://cdn.ckeditor.com; ".
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ".
                "font-src 'self' https://fonts.gstatic.com data:; ".
                "img-src 'self' data: https: http: blob:; ".
                "connect-src 'self' https://api.stripe.com https://cdn.ckeditor.com; ".
                "frame-src 'self' https://js.stripe.com; ".
                "object-src 'none'; ".
                "base-uri 'self'; ".
                "form-action 'self'";
        $response->headers->set('Content-Security-Policy', $csp, false);

        return $response;
    }
}
