<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resolves public-disk media paths to URLs, falling back to a bundled
 * placeholder when the file is missing on disk. Seeded/legacy records often
 * reference uploads that were never shipped, and a broken image is worse than
 * a tasteful placeholder.
 */
class Media
{
    public const SQUARE_PLACEHOLDER = 'images/placeholder-square.svg';
    public const WIDE_PLACEHOLDER = 'images/placeholder-wide.svg';

    public static function url(?string $path, string $fallback = self::SQUARE_PLACEHOLDER): string
    {
        if (blank($path)) {
            return asset($fallback);
        }

<<<<<<< HEAD
        if (Str::startsWith($path, ['https://', 'http://'])) {
            return $path;
        }

        if (Str::startsWith($path, '/storage/')) {
            return url($path);
        }

        if (Str::startsWith($path, '/images/')) {
            return asset(ltrim($path, '/'));
        }

=======
>>>>>>> origin/main
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return asset($fallback);
        }

        $url = $disk->url($path);

        // Ensure the URL is always absolute
        if (! Str::startsWith($url, ['http://', 'https://'])) {
            // If it's a relative path, make it absolute using APP_URL
            $appUrl = rtrim(config('app.url'), '/');
            if (Str::startsWith($url, '/')) {
                $url = $appUrl . $url;
            } else {
                $url = $appUrl . '/' . ltrim($url, '/');
            }
        }

        return $url;
    }
}
