<?php

/**
 * Haarsingharr — production front controller
 * For cPanel deployments: copy this file + assets/ from Laravel's public/
 * into your /home/CPANEL_USER/public_html/ and update the require paths
 * below to point to the private Laravel application directory.
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// On cPanel the path should resolve to the private app directory (one level above public_html).
// Default assumes the layout documented in README.md.
require __DIR__.'/../haarsingharr/vendor/autoload.php';
$app = require_once __DIR__.'/../haarsingharr/bootstrap/app.php';

// Bind Kernel & handle the request
$kernel = $app->make(Kernel::class);
$response = tap($kernel->handle(
    $request = Request::capture()
))->send();
$kernel->terminate($request, $response);
