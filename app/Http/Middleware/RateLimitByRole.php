<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RateLimitByRole
{
    public function __construct(private readonly RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next, string $tier = 'guest'): Response
    {
        $decay = match ($tier) {
            'staff'  => 60,   // 1 attempt/second
            'admin'  => 600,  // 1 attempt/10 seconds
            'checkout' => 60, // 1 attempt/second
            'review'  => 600,
            'login'   => 60 * 15, // 5 attempts per 15 minutes
            'reset'   => 60 * 60,
            default => 60,
        };

        $attempts = match ($tier) {
            'login'  => 5,
            'reset'  => 3,
            'review' => 3,
            'checkout' => 10,
            default  => 60,
        };

        $key = $this->keyFor($request, $tier);
        if ($this->limiter->tooManyAttempts($key, $attempts)) {
            $retryAfter = $this->limiter->availableIn($key);
            return response()->json([
                'message' => 'Too many attempts. Try again in '.ceil($retryAfter / 60).' minutes.',
            ], 429)->withHeaders(['Retry-After' => $retryAfter]);
        }
        $this->limiter->hit($key, $decay);

        return $next($request);
    }

    private function keyFor(Request $request, string $tier): string
    {
        $id = $request->user()?->id ?: Str::slug($request->ip());
        return Str::lower($tier).'|'.$id;
    }
}
