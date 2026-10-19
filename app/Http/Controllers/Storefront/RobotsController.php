<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function index(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /account',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /webhooks/',
            'Disallow: /payments/',
            'Disallow: /track-order',
            '',
            'Sitemap: '.config('app.url').'/sitemap.xml',
        ];

        if (app()->environment('production')) {
            $content = implode("\n", $lines);
        } else {
            $content = "User-agent: *\nDisallow: /";
        }

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
