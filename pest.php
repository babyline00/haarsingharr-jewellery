<?php

use Pest\Growth\Exports;
use Pest\Growth\Settings;
use Pest\Growth\Performance;
use Pest\Growth\Coverage;
use Pest\Growth\Memory;

// Pest configuration — fast, focused tests.
// Tests use SQLite in-memory for isolation; integration tests against MySQL
// can be run separately via `./vendor/bin/pest --testsuite=integration`.

exports()->trimConstrained(false);

settings()
    ->expect('App\\')
    ->toBeClasses()
    ->not->toBeFinal();

// Coverage thresholds
coverage()
    ->enabled(fn (Coverage $c) => $c
        ->buildsThrough('xdebug')
        ->excludes(['tests/', 'database/', 'bootstrap/', 'config/', 'routes/'])
        ->minimum(80));
