<?php

use Illuminate\Support\Facades\Route;

// API routes — kept minimal. The storefront is server-rendered Blade.
// Use API routes for: webhook receivers (already in web.php), Webhooks
// from external systems, or future mobile-app integration.

Route::middleware('auth:sanctum')->group(function () {
    // Reserved for future API endpoints (mobile app, integrations).
});
