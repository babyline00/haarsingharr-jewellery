<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Generates deterministic, on-brand SVG artwork for the demo catalogue.
 *
 * The development seeder ships products with no real photography; rather than
 * render the same placeholder tile everywhere (or a broken image), each record
 * gets a stable piece of abstract jewellery art derived from its seed string.
 * Because the output is deterministic, re-seeding reproduces identical files.
 */
class DemoArtwork
{
    /** [background start, background end, gem base, gem light, accent] */
    private const PALETTES = [
        ['#FBF8F2', '#F1E8D8', '#C8A968', '#E6D6AC', '#A8854A'],
        ['#F4EEE2', '#EADFCB', '#B47B5A', '#DCB39A', '#8F5A3C'],
        ['#FBF8F2', '#EFE6D6', '#3E2B33', '#6E4E5A', '#C8A968'],
        ['#F4EEE2', '#FBF8F2', '#3F8E5C', '#84C69C', '#2F6B45'],
        ['#FBF8F2', '#F0EEE6', '#6E7FA3', '#A9B6D2', '#4C5A78'],
        ['#F8F2E8', '#FBF8F2', '#B23A48', '#DC808A', '#8C2A35'],
    ];

    private const SHAPES = [
        'diamond', 'oval', 'teardrop', 'round', 'marquise', 'hex',
    ];

    public static function product(string $seed): string
    {
        return self::square(self::palette($seed), self::shape($seed));
    }

    public static function wide(string $seed): string
    {
        $palette = self::palette($seed);
        $shape = self::shape($seed);
        [$bg1, $bg2, , , $accent] = $palette;

        $gemGradient = self::gemGradient($palette);
        $gem = self::gem($shape);

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 700" width="1600" height="700" role="img" aria-label="Haarsingharr jewellery">
          <defs>
            <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="{$bg1}"/>
              <stop offset="0.55" stop-color="{$bg2}"/>
              <stop offset="1" stop-color="{$bg1}"/>
            </linearGradient>
            <radialGradient id="glow" cx="0.5" cy="0.45" r="0.65">
              <stop offset="0" stop-color="#FFFFFF" stop-opacity="0.85"/>
              <stop offset="1" stop-color="#FFFFFF" stop-opacity="0"/>
            </radialGradient>
            {$gemGradient}
          </defs>
          <rect width="1600" height="700" fill="url(#bg)"/>
          <rect width="1600" height="700" fill="url(#glow)"/>
          <g transform="translate(800 330) scale(1.15)">
            <circle r="200" fill="none" stroke="{$accent}" stroke-opacity="0.22" stroke-width="1.5"/>
            {$gem}
          </g>
          <text x="800" y="632" text-anchor="middle" font-family="Inter, Helvetica, Arial, sans-serif" font-size="26" letter-spacing="16" fill="{$accent}" fill-opacity="0.6">HAARSINGHARR</text>
          <text x="800" y="666" text-anchor="middle" font-family="Inter, Helvetica, Arial, sans-serif" font-size="15" letter-spacing="7" fill="#6F6A63" fill-opacity="0.6">JEWELRY FOR EVERY YOU</text>
        </svg>
        SVG;
    }

    /**
     * Write the SVG to the public disk unless it already exists.
     */
    public static function ensure(string $path, string $svg): bool
    {
        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            return false;
        }

        $disk->put($path, $svg);

        return true;
    }

    private static function square(array $palette, string $shape): string
    {
        [$bg1, $bg2, , , $accent] = $palette;

        $gemGradient = self::gemGradient($palette);
        $gem = self::gem($shape);

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" width="800" height="800" role="img" aria-label="Haarsingharr jewellery">
          <defs>
            <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="{$bg1}"/>
              <stop offset="1" stop-color="{$bg2}"/>
            </linearGradient>
            <radialGradient id="glow" cx="0.5" cy="0.45" r="0.62">
              <stop offset="0" stop-color="#FFFFFF" stop-opacity="0.85"/>
              <stop offset="1" stop-color="#FFFFFF" stop-opacity="0"/>
            </radialGradient>
            {$gemGradient}
          </defs>
          <rect width="800" height="800" fill="url(#bg)"/>
          <rect width="800" height="800" fill="url(#glow)"/>
          <g transform="translate(400 372)">
            <circle r="205" fill="none" stroke="{$accent}" stroke-opacity="0.22" stroke-width="1.5"/>
            {$gem}
          </g>
          <text x="400" y="744" text-anchor="middle" font-family="Inter, Helvetica, Arial, sans-serif" font-size="17" letter-spacing="10" fill="{$accent}" fill-opacity="0.55">HAARSINGHARR</text>
        </svg>
        SVG;
    }

    private static function gemGradient(array $palette): string
    {
        [, , $gem, $gemLight, $accent] = $palette;

        return <<<SVG
        <linearGradient id="gem" x1="0" y1="0" x2="0.35" y2="1">
          <stop offset="0" stop-color="{$gemLight}"/>
          <stop offset="0.55" stop-color="{$gem}"/>
          <stop offset="1" stop-color="{$accent}"/>
        </linearGradient>
        SVG;
    }

    private static function gem(string $shape): string
    {
        $facet = 'fill="none" stroke="#FFFFFF" stroke-opacity="0.30" stroke-width="2"';

        $shapes = [
            'diamond' => '<polygon points="0,-150 150,0 0,150 -150,0" fill="url(#gem)"/>'
                .'<path d="M-150 0 L0 -34 L150 0 M0 -150 L0 -34 L0 150" '.$facet.'/>',
            'oval' => '<ellipse rx="115" ry="152" fill="url(#gem)"/>'
                .'<ellipse rx="66" ry="96" cx="-24" cy="-26" fill="#FFFFFF" fill-opacity="0.16"/>',
            'teardrop' => '<path d="M0 -162 C 96 -72 122 12 0 142 C -122 12 -96 -72 0 -162 Z" fill="url(#gem)"/>'
                .'<path d="M0 -162 C 42 -100 52 -40 0 22" '.$facet.'/>',
            'round' => '<circle r="146" fill="url(#gem)"/>'
                .'<circle r="88" cx="-26" cy="-32" fill="#FFFFFF" fill-opacity="0.15"/>',
            'marquise' => '<path d="M0 -162 C 112 -62 112 62 0 162 C -112 62 -112 -62 0 -162 Z" fill="url(#gem)"/>'
                .'<path d="M0 -162 L0 162" '.$facet.'/>',
            'hex' => '<polygon points="0,-150 130,-76 130,76 0,150 -130,76 -130,-76" fill="url(#gem)"/>'
                .'<path d="M-130 -76 L0 0 L130 -76 M-130 76 L0 0 L130 76 M0 -150 L0 0 L0 150" '.$facet.'/>',
        ];

        return $shapes[$shape];
    }

    private static function palette(string $seed): array
    {
        return self::PALETTES[self::hash($seed) % count(self::PALETTES)];
    }

    private static function shape(string $seed): string
    {
        return self::SHAPES[intdiv(self::hash($seed), 7) % count(self::SHAPES)];
    }

    private static function hash(string $seed): int
    {
        return (int) sprintf('%u', crc32($seed));
    }
}
