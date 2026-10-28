<?php

namespace App\Support;

use App\Models\StoreSetting;

class Branding
{
    public static function logoUrl(): string
    {
        return Media::url(StoreSetting::get('store_logo_url', '/images/logo.png'), 'images/logo.png');
    }
}
