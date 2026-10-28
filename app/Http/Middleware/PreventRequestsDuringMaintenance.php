<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as FrameworkMaintenance;

/**
 * Maintenance gate with Haarsingharr extras layered on the framework's:
 *
 *  - the "artisan-down" secret path + bypass cookie (/{secret} grants access),
 *  - an IP allowlist via MAINTENANCE_ALLOWED_IPS (comma-separated).
 */
class PreventRequestsDuringMaintenance extends FrameworkMaintenance
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    protected function inExceptArray($request)
    {
        $allowedIps = array_filter(array_map(
            'trim',
            explode(',', (string) env('MAINTENANCE_ALLOWED_IPS', '')),
        ));

        if (in_array($request->ip(), $allowedIps, true)) {
            return true;
        }

        return parent::inExceptArray($request);
    }
}
