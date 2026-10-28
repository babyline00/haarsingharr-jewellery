<?php

/**
 * Shared-hosting entry point.
 *
 * On hosts where the document root cannot be pointed at the `public/`
 * directory, place this file in the project root. It delegates to the real
 * front controller in `public/index.php`, which resolves all paths relative
 * to itself.
 *
 * Prefer pointing the host's document root at `public/` instead; this file is
 * a fallback for hosts that do not allow that.
 */
require __DIR__.'/public/index.php';
