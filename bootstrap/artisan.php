<?php

use Symfony\Component\Console\Input\ArgvInput;

/**
 * Console bootstrap for the project's root `artisan` script.
 *
 * The root script resolves this file and invokes the returned callable with
 * the CLI arguments (minus the script name):
 *
 *     $status = (int) (require $app->bootstrapPath('artisan.php'))(...array_slice($argv, 1));
 *
 * When invoked that way the Application instance is already in scope, so it
 * is reused; otherwise it is loaded fresh from app.php.
 *
 * @var \Illuminate\Foundation\Application|null $app
 */

$app = $app ?? require __DIR__.'/app.php';

return function (string ...$args) use ($app): int {
    return (int) $app->handleCommand(new ArgvInput(['artisan', ...$args]));
};
