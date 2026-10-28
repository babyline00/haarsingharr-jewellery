<?php

namespace App\Support;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resolves branding values from store settings, supporting uploaded public-disk
 * paths, absolute URLs and bundled asset paths such as /images/logo.png.
 */
class Brand
{
    public static function logoUrl(): string
    {
        return self::resolve(
            StoreSetting::get('store_logo_url', '/images/logo.png'),
            'images/logo.png',
        );
    }

    public static function logoHeight(): int
    {
        return max(24, min(160, (int) StoreSetting::get('store_logo_height', 56)));
    }

    public static function faviconUrl(): string
    {
        return self::resolve(
            StoreSetting::get('store_favicon_url', '/images/favicon.ico'),
            'images/favicon.ico',
        );
    }

    public static function giftImageUrl(): ?string
    {
        $path = StoreSetting::get('gift_popup_image_url');
        return blank($path) ? null : self::resolve($path, 'images/placeholder-wide.svg');
    }

    public static function resolve(?string $path, string $fallback = 'images/placeholder-square.svg'): string
    {
        if (blank($path)) {
            return asset($fallback);
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, '/')) {
            return asset(ltrim($path, '/'));
        }

        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            $url = $disk->url($path);
            return Str::startsWith($url, ['http://', 'https://'])
                ? $url
                : rtrim(config('app.url'), '/').'/'.ltrim($url, '/');
        }

        return asset($fallback);
    }
}
