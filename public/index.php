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

// Resolve the private application directory. On cPanel this is the app folder
// one level above public_html (see README.md); for local/standard installs it is
// simply the parent of public/.
$appBase = is_dir(__DIR__.'/../haarsingharr/bootstrap') ? __DIR__.'/../haarsingharr' : dirname(__DIR__);

require $appBase.'/vendor/autoload.php';
$app = require_once $appBase.'/bootstrap/app.php';

// Bind Kernel & handle the request
$kernel = $app->make(Kernel::class);
$response = tap($kernel->handle(
    $request = Request::capture()
))->send();
$kernel->terminate($request, $response);
